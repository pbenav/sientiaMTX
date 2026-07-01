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

            // Asignaciones
            if (!empty($data['assigned_to']) || !empty($data['assigned_groups'])) {
                $this->syncAssignments($activity, $data);
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

            $updateData = array_filter(
                array_intersect_key($data, array_flip($fillable)),
                fn($v) => $v !== null
            );

            // Status
            if (!empty($data['status'])) {
                $updateData['status'] = is_array($data['status'])
                    ? $data['status']
                    : ['value' => $data['status']];
            }

            // Metadata: merge parcial de campos específicos del tipo
            $newMetadata = $this->buildMetadata($activity->type, $data);
            if (!empty($newMetadata) || !empty($data['metadata'])) {
                $updateData['metadata'] = array_merge(
                    $activity->metadata ?? [],
                    $data['metadata'] ?? [],
                    $newMetadata
                );
            }

            $activity->update($updateData);

            if (array_key_exists('assigned_to', $data) || array_key_exists('assigned_groups', $data)) {
                $this->syncAssignments($activity, $data);
            }

            if (!empty($files)) {
                $this->handleAttachments($activity, $files);
            }

            if (array_key_exists('tags', $data)) {
                $this->syncTags($activity, $data['tags'] ?? []);
            }

            $this->recordHistory($activity, auth()->user(), 'updated', $oldValues, $activity->fresh()->toArray());

            return $activity->fresh();
        });
    }

    // ─── Estado ───────────────────────────────────────────────────────────────

    public function changeStatus(Activity $activity, string $statusValue): Activity
    {
        $oldStatus = $activity->status;
        $activity->update(['status' => ['value' => $statusValue]]);

        $this->recordHistory($activity, auth()->user(), 'status_changed', $oldStatus, ['value' => $statusValue]);

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

    public function syncAssignments(Activity $activity, array $data): void
    {
        // Borrar asignaciones actuales
        $activity->assignments()->delete();

        $assignedBy  = auth()->id();
        $assignedAt  = now();
        $userIds     = $data['assigned_to'] ?? [];
        $groupIds    = $data['assigned_groups'] ?? [];

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

        $this->recordHistory($activity, auth()->user(), 'assigned', null, compact('userIds', 'groupIds'));
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
        $user      = auth()->user();
        $isManager = $team->isManager($user);

        $query = Activity::byTeam($team->id)
            ->active()
            ->with(['creator', 'assignedTo', 'kanbanColumn', 'tags', 'assignedGroups', 'expediente',
                'children' => function ($q) {
                    $q->where('is_archived', false)->orderBy('created_at');
                },
            ]);

        // Visibilidad y Control de Jerarquía (Garantizando compatibilidad con task_assignments legacy)
        if ($isManager) {
            // GESTIÓN (Managers): Ven todo lo público y esqueleto del equipo, pero NUNCA actividades PRIVADAS de otros
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

            // Para evitar ruido visual en el listado general, ven las raíces o plantillas maestras.
            if (empty($filters['search'])) {
                $query->where(function ($q) {
                    $q->whereNull('parent_id')
                      ->orWhere('is_template', true);
                });
            }
        } else {
            // EJECUCIÓN (Miembros): Ven su trabajo asignado (tanto en activity_assignments como en task_assignments legacy),
            // lo que ellos mismos han creado, o actividades públicas sin asignar.
            $query->where(function ($q) use ($user) {
                // 1. Creadas por el usuario
                $q->where('created_by_id', $user->id);

                // 2. Actividades públicas "PURAS" (sin asignaciones)
                $q->orWhere(function ($subq) {
                    $subq->where('visibility', 'public')
                         ->whereDoesntHave('assignedTo')
                         ->whereDoesntHave('assignedGroups')
                         ->whereNotExists(function ($legacy) {
                             $legacy->select(\DB::raw(1))
                                 ->from('activity_task_mapping')
                                 ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                 ->whereColumn('activity_task_mapping.activity_id', 'activities.id');
                         });
                });

                // 3. Asignaciones directas en activity_assignments (nueva arquitectura)
                $q->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                  ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));

                // 4. Asignaciones legacy en task_assignments a través de activity_task_mapping
                $q->orWhereExists(function ($sub) use ($user) {
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

            // Evitar ruido visual de subtareas en el listado general solo para actividades donde el usuario es mero creador/público,
            // pero SIEMPRE mostrar las actividades/tareas que tenga asignadas explícitamente (aunque sean instancias/subtareas).
            if (empty($filters['search'])) {
                $query->where(function ($q) use ($user) {
                    $q->whereNull('parent_id')
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
            }
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
            $query->where(fn($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term));
        }

        if (!empty($filters['archived'])) {
            $query->archived();
        }

        // Ordenación
        $allowedSorts = ['due_date', 'scheduled_date', 'priority', 'title', 'created_at', 'progress_percentage'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                  ->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function paginate(Team $team, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->search($team, $filters)->paginate($perPage);
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
            'decision' => 'proposed',
            'meeting'  => 'scheduled',
            'reminder' => 'pending',
            default    => 'pending',
        }];
    }

    protected function buildMetadata(string $type, array $data): array
    {
        // metadata base si viene del formulario
        $base = $data['metadata'] ?? [];

        $loader = app(\App\Services\TemplateLoader::class);
        $template = $loader->getTemplate($type);

        $specifics = [];
        if ($template && isset($template['properties'])) {
            foreach ($template['properties'] as $key => $rules) {
                if (array_key_exists($key, $data)) {
                    $specifics[$key] = $data[$key];
                } elseif (isset($rules['default'])) {
                    $specifics[$key] = $rules['default'];
                }
            }
        }

        // Limpiamos nulos para no ensuciar el JSON final, igual que hacía el array_filter
        $specifics = array_filter($specifics, fn($val) => $val !== null);

        return array_merge($base, $specifics);
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
            'user_id'     => $user?->id ?? auth()->id(),
            'action'      => $action,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'notes'       => $notes,
        ]);
    }
}
