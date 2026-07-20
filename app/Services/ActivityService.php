<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAssignment;
use App\Models\ActivityAttachment;
use App\Models\ActivityHistory;
use App\Models\Team;
use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio central de Actividades.
 *
 * Toda la lógica de negocio pasa por aquí.
 * Ningún controlador hace queries directas a la tabla activities.
 *
 * Registro automático de historial en cada operación.
 */
class ActivityService
{
    // ─── Creación ─────────────────────────────────────────────────────────────

    /**
     * Crea una actividad de cualquier tipo con toda su infraestructura.
     *
     * @param  Team   $team       Equipo propietario
     * @param  string $type       Tipo: task | document | note | link | decision | meeting | reminder
     * @param  array  $data       Datos validados del formulario
     * @param  array  $files      Archivos adjuntos (UploadedFile[])
     * @return Activity
     */
    public function create(Team $team, string $type, array $data, array $files = []): Activity
    {
        return DB::transaction(function () use ($team, $type, $data, $files) {
            $activity = Activity::create([
                'team_id'             => $team->id,
                'created_by_id'       => auth()->id(),
                'type'                => $type,
                'title'               => $data['title'],
                'description'         => $data['description'] ?? null,
                'status'              => $this->buildInitialStatus($type, $data),
                'metadata'            => $this->buildMetadata($type, $data),
                'visibility'          => $data['visibility'] ?? 'private',
                'due_date'            => $data['due_date'] ?? null,
                'scheduled_date'      => $data['scheduled_date'] ?? null,
                'original_due_date'   => $data['due_date'] ?? null,
                'priority'            => $data['priority'] ?? 'medium',
                'auto_priority'       => $data['auto_priority'] ?? false,
                'progress_percentage' => $data['progress_percentage'] ?? 0,
                'parent_id'           => $data['parent_id'] ?? null,
                'expediente_id'       => $data['expediente_id'] ?? null,
                'is_template'         => $data['is_template'] ?? false,
                'kanban_column_id'    => $data['kanban_column_id'] ?? null,
                'kanban_order'        => $data['kanban_order'] ?? null,
                'matrix_order'        => $data['matrix_order'] ?? null,
            ]);

            // Determinar si es Plan Maestro a partir de properties dinámicas o datos directos
            $assignmentMode = $data['assignment_mode'] ?? data_get($activity->metadata, 'assignment_mode', 'shared');
            if ($type === 'task' && $assignmentMode === 'distributed') {
                $activity->update(['is_template' => true]);
            }

            // Asignaciones
            $assignedUserIds = [];
            if (!empty($data['assigned_to']) || !empty($data['assigned_groups'])) {
                $assignedUserIds = $this->syncAssignments($activity, $data);
            }

            // Adjuntos
            if (!empty($files)) {
                $this->handleAttachments($activity, $files);
            }

            // Etiquetas
            if (!empty($data['tags'])) {
                $this->syncTags($activity, $data['tags']);
            }

            // Si se ha asignado a columna Kanban pero no hay order, lo ponemos al final
            if ($activity->kanban_column_id && !$activity->kanban_order) {
                $maxOrder = Activity::where('kanban_column_id', $activity->kanban_column_id)->max('kanban_order') ?? 0;
                $activity->update(['kanban_order' => $maxOrder + 1]);
            }

            $this->recordHistory($activity, auth()->user(), 'created', null, $activity->toArray());

            if ($activity->is_template && $activity->type === 'task') {
                $this->syncDistributedInstances($activity, $assignedUserIds, $data);
            }

            $this->notifyGuests($activity);

            return $activity->fresh();
        });
    }

    // ─── Actualización ────────────────────────────────────────────────────────

    public function update(Activity $activity, array $data, array $files = []): Activity
    {
        return DB::transaction(function () use ($activity, $data, $files) {
            $oldValues = $activity->toArray();

            $fillable = [
                'title', 'description', 'visibility', 'due_date', 'scheduled_date',
                'priority', 'auto_priority', 'progress_percentage',
                'parent_id', 'expediente_id', 'is_template',
                'kanban_column_id', 'kanban_order', 'matrix_order',
            ];

            $updateData = array_intersect_key($data, array_flip($fillable));

            // Status
            if (!empty($data['status'])) {
                $updateData['status'] = is_array($data['status'])
                    ? $data['status']
                    : ['value' => $data['status']];
            }

            // Metadata: merge parcial de campos específicos del tipo
            // Recargamos el modelo para tener los datos más recientes de la BD
            // (ej. capítulos agregados vía modal antes de guardar)
            $activity->refresh();
            $newMetadata = $this->buildMetadata($activity->type, $data, true);
            if (!empty($newMetadata) || !empty($data['metadata'])) {
                $updateData['metadata'] = array_merge(
                    $activity->metadata ?? [],
                    $data['metadata'] ?? [],
                    $newMetadata
                );
            }

            $activity->update($updateData);

            $assignmentMode = $data['assignment_mode'] ?? data_get($activity->metadata, 'assignment_mode');
            if ($activity->type === 'task' && $assignmentMode !== null) {
                if ($assignmentMode === 'distributed' && !$activity->is_template) {
                    $activity->update(['is_template' => true]);
                } elseif ($assignmentMode === 'shared' && $activity->is_template) {
                    $activity->update(['is_template' => false]);
                }
            }

            if (isset($updateData['status'])) {
                $newStatus = data_get($updateData, 'status.value');
                $oldStatus = data_get($oldValues, 'status.value');

                if ($newStatus === 'completed') {
                    $this->cascadeCompletion($activity);
                    $activity->notifyCoordinatorsIfCompleted();
                } elseif ($newStatus === 'blocked' && $oldStatus !== 'blocked') {
                    $activity->notifyCreatorAndCoordinators(new \App\Notifications\TaskBlockedNotification($activity, auth()->user()));
                }
            }

            $oldProgress = (int) ($oldValues['progress_percentage'] ?? 0);
            $newProgress = (int) ($updateData['progress_percentage'] ?? $oldProgress);

            if ($newProgress >= 50 && $oldProgress < 50) {
                 $activity->notifyCreatorAndCoordinators(new \App\Notifications\TaskEventNotification($activity, 'milestone_50'));
            }
            if ($newProgress >= 75 && $oldProgress < 75) {
                 $activity->notifyCreatorAndCoordinators(new \App\Notifications\TaskEventNotification($activity, 'milestone_75'));
            }

            $assignedUserIds = [];
            if (array_key_exists('assigned_to', $data) || array_key_exists('assigned_groups', $data)) {
                $assignedUserIds = $this->syncAssignments($activity, $data);
            } else {
                $assignedUserIds = $this->getUniqueAssignedUserIds($activity);
            }

            if (!empty($files)) {
                $this->handleAttachments($activity, $files);
            }

            if (array_key_exists('tags', $data)) {
                $this->syncTags($activity, $data['tags'] ?? []);
            }

            $this->recordHistory($activity, auth()->user(), 'updated', $oldValues, $activity->fresh()->toArray());

            if ($activity->is_template && $activity->type === 'task') {
                $this->syncDistributedInstances($activity, $assignedUserIds, $data);
            } elseif (!$activity->is_template && data_get($oldValues, 'is_template') === true && $activity->type === 'task') {
                $activity->children()
                    ->where('is_template', false)
                    ->where(function($q) {
                        $q->whereNull('metadata->is_occurrence')
                          ->orWhere('metadata->is_occurrence', false);
                    })
                    ->each(fn($c) => $c->delete());
            }

            $this->notifyGuests($activity);

            return $activity->fresh();
        });
    }

    // ─── Estado ───────────────────────────────────────────────────────────────

    public function changeStatus(Activity $activity, string $statusValue): Activity
    {
        $oldStatus = $activity->status;
        $activity->update(['status' => ['value' => $statusValue]]);

        $this->recordHistory($activity, auth()->user(), 'status_changed', $oldStatus, ['value' => $statusValue]);

        if ($statusValue === 'completed') {
            $this->cascadeCompletion($activity);
            $activity->notifyCoordinatorsIfCompleted();
        } elseif ($statusValue === 'blocked' && (!isset($oldStatus['value']) || $oldStatus['value'] !== 'blocked')) {
            $activity->notifyCreatorAndCoordinators(new \App\Notifications\TaskBlockedNotification($activity, auth()->user()));
        }

        return $activity->fresh();
    }

    // ─── Archivo/Restauración ─────────────────────────────────────────────────

    public function archive(Activity $activity): void
    {
        $activity->update(['is_archived' => true]);
        $this->recordHistory($activity, auth()->user(), 'archived');
    }

    public function unarchive(Activity $activity): void
    {
        $activity->update(['is_archived' => false]);
        $this->recordHistory($activity, auth()->user(), 'unarchived');
    }

    // ─── Eliminación ──────────────────────────────────────────────────────────

    public function delete(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            $this->recordHistory($activity, auth()->user(), 'deleted');

            // Delete remote Google items to prevent orphans
            try {
                if ($activity->google_task_id || $activity->google_calendar_event_id) {
                    $googleService = app(\App\Services\GoogleService::class);
                    $user = auth()->user();
                    if ($user && $googleService->setTokenForUser($user, $activity->team_id)) {
                        if ($activity->google_task_id && $activity->google_task_list_id) {
                            $googleService->deleteTask($activity->google_task_list_id, $activity->google_task_id);
                        }
                        if ($activity->google_calendar_event_id) {
                            $googleService->deleteEvent($activity->google_calendar_event_id, $activity->google_calendar_id ?? 'primary');
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error deleting remote Google events during Activity delete: ' . $e->getMessage());
            }

            $activity->delete();
        });
    }

    public function restore(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            $activity->restore();
            $this->recordHistory($activity, auth()->user(), 'restored');
        });
    }

    // ─── Asignaciones ─────────────────────────────────────────────────────────

    public function syncAssignments(Activity $activity, array $data): array
    {
        $previousUserIds = $activity->assignments()->whereNotNull('user_id')->pluck('user_id')->toArray();
        
        // Borrar asignaciones actuales
        $activity->assignments()->delete();

        $assignedBy  = auth()->id();
        $assignedAt  = now();
        $userIds     = $data['assigned_to'] ?? [];
        $groupIds    = $data['assigned_groups'] ?? [];

        // Expand group members into userIds if they are not already there
        // Actually, previous task assignment expansion was handled in Controller/Service
        // but here we just store the group_id. We should also notify users in those groups!
        // But for simplicity, let's keep it to direct user assignments first, or expand them for notifications.
        $notifyUserIds = collect($userIds);
        foreach ($groupIds as $groupId) {
            $group = $activity->team->groups()->find($groupId);
            if ($group) {
                $notifyUserIds = $notifyUserIds->merge($group->users->pluck('id'));
            }
        }
        $uniqueNotifyUserIds = $notifyUserIds->unique()->toArray();

        foreach ($userIds as $userId) {
            ActivityAssignment::create([
                'activity_id'    => $activity->id,
                'user_id'        => $userId,
                'group_id'       => null,
                'assigned_by_id' => $assignedBy,
                'assigned_at'    => $assignedAt,
            ]);
        }

        foreach ($groupIds as $groupId) {
            ActivityAssignment::create([
                'activity_id'    => $activity->id,
                'user_id'        => null,
                'group_id'       => $groupId,
                'assigned_by_id' => $assignedBy,
                'assigned_at'    => $assignedAt,
            ]);
        }

        $isDistributedPlan = ($activity->is_template && $activity->type === 'task');

        if (!$isDistributedPlan) {
            $newUserIds = array_diff($uniqueNotifyUserIds, $previousUserIds);
            foreach ($newUserIds as $userId) {
                if ($userId && $userId !== $assignedBy) {
                    try {
                        \App\Models\User::find($userId)?->notify(new \App\Notifications\TaskAssignedNotification($activity, auth()->user()));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send TaskAssignedNotification: " . $e->getMessage());
                    }
                }
            }
        }

        $this->recordHistory($activity, auth()->user(), 'assigned', null, compact('userIds', 'groupIds'));

        return $uniqueNotifyUserIds;
    }

    protected function getUniqueAssignedUserIds(Activity $activity): array
    {
        $userIds = $activity->assignedTo->pluck('id');
        foreach ($activity->assignedGroups as $group) {
            $userIds = $userIds->merge($group->users->pluck('id'));
        }
        return $userIds->unique()->toArray();
    }

    protected function syncDistributedInstances(Activity $parent, array $userIds, array $data): void
    {
        $existingInstances = $parent->children()
            ->where('is_template', false)
            ->where(function($q) {
                $q->whereNull('metadata->is_occurrence')
                  ->orWhere('metadata->is_occurrence', false)
                  ->orWhere('metadata->is_occurrence', 'false');
            })
            ->get();
            
        $existingUserIds = [];
        foreach ($existingInstances as $instance) {
            $assignedUserId = $instance->assignments()->first()?->user_id;
            if ($assignedUserId) {
                $existingUserIds[$assignedUserId] = $instance;
            }
        }

        $userIdsToKeep = array_intersect(array_keys($existingUserIds), $userIds);
        $userIdsToDelete = array_diff(array_keys($existingUserIds), $userIds);
        
        foreach ($userIdsToDelete as $id) {
            $existingUserIds[$id]->delete();
        }

        $userIdsToCreate = array_diff($userIds, array_keys($existingUserIds));
        
        foreach ($userIdsToCreate as $userId) {
            $childData = $data;
            $childData['title'] = $parent->title;
            $childData['description'] = $parent->description;
            $childData['priority'] = $parent->priority;
            $childData['visibility'] = 'private';
            $childData['parent_id'] = $parent->id;
            $childData['is_template'] = false;
            $childData['assigned_to'] = [$userId];
            $childData['assigned_groups'] = [];
            
            $childData['metadata'] = array_merge($parent->metadata ?? [], [
                'is_distributed_instance' => true,
                'assignment_mode' => 'shared'
            ]);

            $instance = Activity::create([
                'team_id' => $parent->team_id,
                'created_by_id' => $parent->created_by_id,
                'type' => $parent->type,
                'title' => $childData['title'],
                'description' => $childData['description'] ?? null,
                'status' => ['value' => 'pending'],
                'metadata' => $childData['metadata'],
                'visibility' => 'private',
                'due_date' => $parent->due_date,
                'scheduled_date' => $parent->scheduled_date,
                'original_due_date' => $parent->original_due_date,
                'priority' => $parent->priority,
                'parent_id' => $parent->id,
                'expediente_id' => $parent->expediente_id,
                'is_template' => false,
            ]);

            ActivityAssignment::create([
                'activity_id' => $instance->id,
                'user_id' => $userId,
                'assigned_by_id' => auth()->id() ?? $parent->created_by_id,
                'assigned_at' => now(),
            ]);

            if (!empty($data['tags'])) {
                $this->syncTags($instance, $data['tags']);
            }

            if ((int)$userId !== (int)auth()->id()) {
                try {
                    \App\Models\User::find($userId)?->notify(new \App\Notifications\TaskAssignedNotification($instance, auth()->user()));
                } catch (\Exception $e) {
                    \Log::error("Failed to notify user for distributed instance: " . $e->getMessage());
                }
            }
        }
        
        foreach ($userIdsToKeep as $id) {
            $instance = $existingUserIds[$id];
            $instance->update([
                'title' => $parent->title,
                'description' => $parent->description,
                'priority' => $parent->priority,
                'due_date' => $parent->due_date,
                'scheduled_date' => $parent->scheduled_date,
                'expediente_id' => $parent->expediente_id,
            ]);
            $meta = array_merge($instance->metadata ?? [], [
                'skills_required' => $parent->metadata['skills_required'] ?? [],
                'cognitive_load' => $parent->metadata['cognitive_load'] ?? 1,
            ]);
            $instance->update(['metadata' => $meta]);
        }
    }

    // ─── Adjuntos ─────────────────────────────────────────────────────────────

    public function handleAttachments(Activity $activity, array $files): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) continue;

            $path = $file->store("activities/{$activity->id}", 'local');

            ActivityAttachment::create([
                'activity_id'    => $activity->id,
                'uploaded_by_id' => auth()->id(),
                'file_name'      => $file->getClientOriginalName(),
                'file_path'      => $path,
                'disk'           => 'local',
                'mime_type'      => $file->getMimeType(),
                'file_size'      => $file->getSize(),
            ]);
        }
    }

    public function deleteAttachment(ActivityAttachment $attachment): void
    {
        if (Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            Storage::disk($attachment->disk)->delete($attachment->file_path);
        }
        $attachment->delete();
    }

    // ─── Etiquetas ────────────────────────────────────────────────────────────

    public function syncTags(Activity $activity, array $tags): void
    {
        $activity->tags()->delete();

        foreach (array_unique($tags) as $tag) {
            if (empty(trim($tag))) continue;
            $activity->tags()->create([
                'tag'       => Str::lower(trim($tag)),
                'color_hex' => '#6b7280',
            ]);
        }
    }

    // ─── Búsqueda ─────────────────────────────────────────────────────────────

    /**
     * Query base filtrada y ordenada, lista para paginar.
     */
    public function search(Team $team, array $filters = [], string $sort = 'due_date', string $dir = 'asc'): Builder
    {
        $user = auth()->user();
        $isManager = $team->isManager($user);

        $query = Activity::byTeam($team->id)
            ->active()
            ->with([
                'creator', 'assignedTo', 'kanbanColumn', 'tags', 'assignedGroups', 'expediente', 'assignedUser', 'skills', 'parent',
                'children' => function($q) use ($user, $isManager) {
                    $q->where('is_archived', false)
                      ->orderBy('created_at')
                      ->visibleTo($user, $isManager);
                }
            ])
            ->notEphemeral();

        // Visibilidad y Control de Jerarquía
        if ($isManager) {
            // GESTIÓN: Ven todo excepto lo que sea estrictamente 'private' de otros usuarios.
            $query->where(function ($q) use ($user) {
                $q->where('visibility', '!=', 'private')
                  ->orWhere('created_by_id', $user->id)
                  ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                  ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)))
                  ->orWhereExists(function ($sub) use ($user) {
                      $sub->select(\DB::raw(1))
                          ->from('activity_task_mapping')
                          ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                          ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                          ->where(function ($a) use ($user) {
                              $a->where('task_assignments.user_id', $user->id)
                                ->orWhereExists(function ($g) use ($user) {
                                    $g->select(\DB::raw(1))
                                      ->from('group_user')
                                      ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                                      ->where('group_user.user_id', $user->id);
                                });
                          });
                  });
            });
        } else {
            // EJECUCIÓN (Miembros): Ven su trabajo asignado, lo que han creado, actividades públicas, o padres de tareas hijas asignadas.
            $query->where(function ($q) use ($user) {
                $q->where('created_by_id', $user->id)
                  ->orWhere(function ($subq) {
                    $subq->where('visibility', 'public')
                         ->whereDoesntHave('assignedTo')
                         ->whereDoesntHave('assignedGroups');
                  })
                  ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                  ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)))
                  ->orWhereExists(function ($sub) use ($user) {
                    $sub->select(\DB::raw(1))
                        ->from('activity_task_mapping')
                        ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                        ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                        ->where(function ($a) use ($user) {
                            $a->where('task_assignments.user_id', $user->id)
                              ->orWhereExists(function ($g) use ($user) {
                                  $g->select(\DB::raw(1))
                                    ->from('group_user')
                                    ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                                    ->where('group_user.user_id', $user->id);
                              });
                        });
                  })
                  ->orWhereHas('children', function ($sub) use ($user) {
                      $sub->whereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                          ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                  });
            });
        }

        // Respetar jerarquía en el listado general: solo mostrar las actividades principales (padres)
        // A MENOS que hayan filtrado explícitamente para ver instancias.
        if (($filters['template_type'] ?? '') !== 'instance') {
            $query->whereNull('parent_id');
        }

        // Filtros
        if (!empty($filters['type'])) {
            is_array($filters['type'])
                ? $query->ofTypes($filters['type'])
                : $query->ofType($filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->whereJsonContains('status->value', $filters['status']);
        } elseif (session('hide_completed_tasks', true)) {
            $query->whereNotIn('status->value', ['completed', 'cancelled']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['expediente_id'])) {
            $query->where('expediente_id', $filters['expediente_id']);
        }

        if (!empty($filters['due_from'])) {
            $query->where('due_date', '>=', $filters['due_from']);
        }

        if (!empty($filters['due_to'])) {
            $query->where('due_date', '<=', $filters['due_to']);
        }

        if (!empty($filters['tag'])) {
            $query->whereHas('tags', fn($q) => $q->where('tag', Str::lower($filters['tag'])));
        }

        if (!empty($filters['assigned_to'])) {
            $query->whereHas('assignedTo', fn($q) => $q->where('users.id', $filters['assigned_to']));
        }

        if (!empty($filters['skill_id'])) {
            $query->where(function($q) use ($filters) {
                $q->whereJsonContains('metadata->skill_id', (int)$filters['skill_id'])
                  ->orWhereHas('skills', fn($s) => $s->where('skills.id', $filters['skill_id']));
            });
        }

        if (!empty($filters['template_type'])) {
            if ($filters['template_type'] === 'template') {
                $query->where('is_template', true);
            } elseif ($filters['template_type'] === 'instance') {
                $query->where('is_template', false)->whereNotNull('parent_id');
            } elseif ($filters['template_type'] === 'normal') {
                $query->where('is_template', false)->whereNull('parent_id');
            }
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhereHas('children', function($subq) use ($term) {
                      $subq->where('title', 'like', $term)
                           ->orWhere('description', 'like', $term);
                  });
            });
        }

        if (!empty($filters['archived'])) {
            $query->archived();
        }

        // Ordenación
        $allowedSorts = ['due_date', 'scheduled_date', 'priority', 'title', 'created_at', 'progress_percentage'];
        if (in_array($sort, $allowedSorts)) {
            if ($sort === 'priority') {
                $dirRaw = $dir === 'desc' ? 'DESC' : 'ASC';
                $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') $dirRaw")
                      ->orderBy('created_at', 'desc');
            } else {
                $query->orderBy($sort, $dir === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                  ->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function paginate(Team $team, array $filters = [], int $perPage = 20, string $sort = 'due_date', string $dir = 'asc'): LengthAwarePaginator
    {
        return $this->search($team, $filters, $sort, $dir)->paginate($perPage);
    }

    // ─── Helpers internos ─────────────────────────────────────────────────────

    protected function buildInitialStatus(string $type, array $data): array
    {
        // Si viene explícito en los datos, lo usamos
        if (!empty($data['status'])) {
            $val = is_array($data['status']) ? $data['status']['value'] : $data['status'];
            return ['value' => $val];
        }

        // Status por defecto según tipo
        return ['value' => match($type) {
            'task'     => 'pending',
            'document' => 'draft',
            'note'     => 'draft',
            'link'     => 'active',
            'agreement' => 'proposed',
            'meeting'  => 'scheduled',
            'reminder' => 'pending',
            default    => 'pending',
        }];
    }

    protected function buildMetadata(string $type, array $data, bool $isUpdate = false): array
    {
        // metadata base si viene del formulario
        $base = $data['metadata'] ?? [];

        // Inicialización de primer capítulo para documentos
        if (!$isUpdate && $type === 'document' && !empty($base['chapter_title'])) {
            $base['chapters'] = [
                [
                    'id' => uniqid('chap_'),
                    'title' => $base['chapter_title'],
                    'content' => $base['chapter_content'] ?? '',
                    'author_id' => auth()->id(),
                    'author_name' => auth()->user()?->name ?? 'Autor',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
            unset($base['chapter_title'], $base['chapter_content']);
        }

        $loader = app(\App\Services\TemplateLoader::class);
        $template = $loader->getTemplate($type);

        $specifics = [];
        if ($template && isset($template['properties'])) {
            foreach ($template['properties'] as $key => $rules) {
                if (array_key_exists($key, $data)) {
                    $specifics[$key] = $data[$key];
                } elseif (!$isUpdate && isset($rules['default']) && !array_key_exists($key, $base)) {
                    // Solo aplicar default si la clave no fue ya construida en $base
                    // (evita que defaults como chapters:[] machaquen capítulos ya inicializados)
                    $specifics[$key] = $rules['default'];
                }
            }
        }

        // Permitimos valores nulos para que el usuario pueda vaciar campos explícitamente

        return array_merge($base, $specifics);
    }

    public function cascadeCompletion(Activity $parent): void
    {
        $parent->children()->whereJsonDoesntContain('status->value', 'completed')
            ->whereJsonDoesntContain('status->value', 'cancelled')
            ->each(function (Activity $child) {
                $oldStatus = $child->status;
                $oldProgress = $child->progress_percentage;

                $meta = $child->metadata ?? [];
                $meta['was_incomplete_before_parent_completion'] = true;
                $meta['original_status_before_cascade'] = $child->status_value;
                $meta['original_progress_before_cascade'] = $oldProgress;

                $child->update([
                    'status' => ['value' => 'completed'],
                    'progress_percentage' => 100,
                    'metadata' => $meta
                ]);

                // Registrar historial de auditoría
                $this->recordHistory(
                    $child,
                    auth()->user(),
                    'status_changed',
                    $oldStatus,
                    ['value' => 'completed', 'progress_percentage' => 100],
                    'Completada automáticamente por cascada al completarse la actividad padre'
                );

                // Recursión para descendientes
                $this->cascadeCompletion($child);
            });
    }

    protected function recordHistory(
        Activity $activity,
        ?User    $user,
        string   $action,
        mixed    $oldValues = null,
        mixed    $newValues = null,
        ?string  $notes = null
    ): void {
        ActivityHistory::create([
            'activity_id' => $activity->id,
            'user_id'     => $user?->id ?? auth()->id() ?? $activity->created_by_id ?? 1,
            'action'      => $action,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'notes'       => $notes,
        ]);
    }

    protected function notifyGuests(Activity $activity): void
    {
        $metadata = $activity->metadata ?? [];
        $modified = false;

        // ── Firmantes EXTERNOS (guests) ──────────────────────────────────────
        if (!empty($metadata['guests'])) {
            $guests        = $metadata['guests'];
            $customMessage = $metadata['invitation_message'] ?? null;

            foreach ($guests as &$guest) {
                if (!empty($guest['notify']) && filter_var($guest['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    try {
                        if ($activity->type === 'agreement') {
                            // Pre-generamos la URL con el host correcto ANTES de encolar el mail.
                            // El worker de cola no tiene contexto HTTP y usaría APP_URL (localhost en dev).
                            $signatureUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                                'agreements.signature.show',
                                now()->addDays(30),
                                [
                                    'team'     => $activity->team_id,
                                    'activity' => $activity->id,
                                    'email'    => $guest['email'],
                                ]
                            );
                            \Illuminate\Support\Facades\Mail::to($guest['email'])->send(
                                new \App\Mail\AgreementSignatureMail($activity, $guest['name'] ?? 'Firmante', auth()->user(), $customMessage, $guest['email'], $signatureUrl)
                            );
                        } else {
                            \Illuminate\Support\Facades\Mail::to($guest['email'])->send(
                                new \App\Mail\MeetingGuestInvitationMail($activity, $guest['name'] ?? 'Invitado', auth()->user(), $customMessage)
                            );
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send guest invitation mail to {$guest['email']}: " . $e->getMessage());
                    }

                    $guest['notify'] = 0;
                    $modified = true;
                }
            }

            $metadata['guests'] = $guests;
        }

        // ── Firmantes INTERNOS (miembros asignados) — solo en decision ───────
        if ($activity->type === 'agreement') {
            // Forzamos la recarga para ignorar caché y traemos también los grupos
            $activity->load(['assignedTo', 'assignedGroups.users']);
            
            $assignedUsers = $activity->assignedTo;
            foreach ($activity->assignedGroups as $group) {
                $assignedUsers = $assignedUsers->merge($group->users);
            }
            $assignedUsers = $assignedUsers->unique('id');

            if ($assignedUsers->isNotEmpty()) {
                $memberSignatures = $metadata['member_signatures'] ?? [];
                $existingUserIds  = collect($memberSignatures)->pluck('user_id')->map(fn($id) => (int)$id)->toArray();

                foreach ($assignedUsers as $user) {
                    if (in_array($user->id, $existingUserIds)) {
                        // Ya está registrado (puede tener ya su firma); no duplicar
                        continue;
                    }

                    // Añadir al registro de firmas internas
                    $memberSignatures[] = [
                        'user_id'         => $user->id,
                        'name'            => $user->name,
                        'signed_at'       => null,
                        'notified_at'     => now()->format('Y-m-d H:i:s'),
                    ];

                    // Notificación interna (push / email / telegram según preferencias del usuario)
                    try {
                        $user->notify(new \App\Notifications\SignatureRequestedNotification($activity, auth()->user()));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send SignatureRequestedNotification to user {$user->id}: " . $e->getMessage());
                    }

                    $modified = true;
                }

                $metadata['member_signatures'] = $memberSignatures;
            }
        }

        if ($modified) {
            $activity->updateQuietly(['metadata' => $metadata]);
        }
    }
}

