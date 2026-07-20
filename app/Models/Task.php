<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

use App\Traits\HasUuid;
use App\Traits\HandlesEisenhowerMatrix;

class Task extends Model
{
    use HasFactory, SoftDeletes, HandlesEisenhowerMatrix, HasUuid;

    protected static function boot(): void
    {
        parent::boot();

        // Alerta de depuración para rastrear accesos al modelo legacy Task
        static::retrieved(function ($task) {
            try {
                if (request()) {
                    $url = request()->fullUrl();
                    $action = request()->route()?->getActionName() ?? 'N/A';
                    \Illuminate\Support\Facades\Log::warning("LEGACY TASK ACCESS: Se ha accedido al modelo obsoleto Task (ID: {$task->id}). URL: {$url} | Action: {$action}");
                } else {
                    \Illuminate\Support\Facades\Log::warning("LEGACY TASK ACCESS: Se ha accedido al modelo obsoleto Task (ID: {$task->id}) desde CLI / Background Job.");
                }
            } catch (\Exception $e) {
                // Silenciar para no interferir con la ejecución normal
            }
        });
    }


    protected $fillable = [
        'team_id',
        'title',
        'description',
        'priority',
        'auto_priority',
        'urgency',
        'status',
        'scheduled_date',
        'due_date',
        'original_due_date',
        'created_by_id',
        'metadata',
        'observations',
        'parent_id',
        'expediente_id',
        'is_template',
        'assigned_user_id',
        'progress_percentage',
        'visibility',
        'google_task_id',
        'google_task_list_id',
        'google_calendar_event_id',
        'google_calendar_id',
        'google_synced_at',
        'is_archived',
        'kanban_column_id',
        'kanban_order',
        'autoprogram_settings',
        'is_out_of_skill_tree',
        'cognitive_load',
        'is_backstage',
        'impact_human_metric',
        'skill_id',
        'matrix_order',
        'service_id',
        'is_autoprogrammable',
        'is_timeline_locked',
    ];
 
    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'original_due_date' => 'datetime',
        'google_synced_at' => 'datetime',
        'is_archived' => 'boolean',
        'autoprogram_settings' => 'array',
        'is_out_of_skill_tree' => 'boolean',
        'cognitive_load' => 'integer',
        'is_backstage' => 'boolean',
        'impact_human_metric' => 'integer',
        'skill_id' => 'integer',
        'service_id' => 'integer',
        'is_autoprogrammable' => 'boolean',
        'auto_priority' => 'boolean',
        'is_timeline_locked' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if task is blocked because its associated service is down
     */
    public function isBlockedByService(): bool
    {
        return $this->service_id && $this->service && $this->service->status === 'down';
    }

    // Relationship: A task belongs to a team
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Relationship: A task was created by a user
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Relationship: A task has many assignments
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    // Relationship: A task is assigned to many users
    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    // Relationship: A task has one calendar event
    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class);
    }

    // Relationship: A task has many history records
    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function getIsEffectivelyPrivateAttribute(): bool
    {
        $hasAssignees = $this->assigned_user_id !== null || 
                        $this->assignedTo->isNotEmpty() || 
                        $this->assignedGroups->isNotEmpty();
        
        return $hasAssignees || $this->visibility === 'private' || is_null($this->visibility);
    }

    public function getPrivacyLevelAttribute(): string
    {
        if (!$this->is_effectively_private) {
            return 'public';
        }

        $userIds = collect([$this->created_by_id, $this->assigned_user_id])->filter();
        
        if ($this->assignedTo->isNotEmpty()) {
            $userIds = $userIds->merge($this->assignedTo->pluck('id'));
        }
        
        if ($this->assignedGroups->isNotEmpty() || $userIds->unique()->count() > 1) {
            return 'semi-private';
        }

        return 'private';
    }

    // Relationship: A task has many tags
    public function tags(): HasMany
    {
        return $this->hasMany(TaskTag::class);
    }

    public function skills(): BelongsToMany
    {
        // La tabla pivot fue renombrada de skill_task -> activity_skills en la migración
        // 2026_07_06_172548_rename_skill_task_to_activity_skills, con FK activity_id.
        // El modelo Task mantiene compatibilidad pasiva; las skills se gestionan desde Activity.
        return $this->belongsToMany(Skill::class, 'activity_skills', 'activity_id', 'skill_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    // Relationship: A task can have a parent task (dependency)
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    // Relationship: A task can have an associated appointment
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'task_id');
    }

    // Relationship: A task can have many subtasks or dependent tasks
    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')
            ->orderBy('title')
            ->orderByRaw('(SELECT name FROM users WHERE users.id = tasks.assigned_user_id)');
    }

    // Relationship: A template task has many instances
    public function instances(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->where('is_template', false)->orderBy('title');
    }

    // Relationship: An instance task belongs to a user
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the associated Activity if this Task has been mapped or converted to the new V2 Activity system.
     */
    public function getActivityAttribute()
    {
        $mapping = \Illuminate\Support\Facades\DB::table('activity_task_mapping')
            ->where('task_id', $this->id)
            ->first();

        if ($mapping) {
            return \App\Models\Activity::find($mapping->activity_id);
        }

        return null;
    }


    /**
     * Check if this task is an instance of a global task
     */
    public function isInstance(): bool
    {
        return !empty($this->parent_id) && !$this->is_template;
    }

    /**
     * Scope a query to only include tasks that are not ephemeral
     */
    public function scopeNotEphemeral($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('metadata->is_ephemeral')
              ->orWhere('metadata->is_ephemeral', false)
              ->orWhere('metadata->is_ephemeral', 'false')
              ->orWhere('metadata->is_ephemeral', '0');
        });
    }

    /**
     * Update task priority automatically based on remaining time
     */
    public function updateAutoPriority()
    {
        if (!$this->auto_priority || !$this->due_date || $this->status === 'completed') {
            return;
        }

        $start = $this->scheduled_date ?: $this->created_at;
        $now = now();
        $due = $this->due_date;

        if ($now->gt($due)) {
            $this->priority = 'critical';
            $this->save();
            return;
        }

        $totalDuration = $start->diffInSeconds($due);
        if ($totalDuration <= 0) return;

        $remainingTime = $now->diffInSeconds($due, false);
        $percentageRemaining = ($remainingTime / $totalDuration) * 100;

        $priorities = ['low', 'medium', 'high', 'critical'];
        // We need to know the "Initial" priority to scale it.
        // But since we don't store it separately, we'll assume the scaling is absolute 
        // or relative to a baseline in metadata.
        // Let's use a simpler approach: fixed mapping by percentage for "Auto" mode.
        
        $newPriority = $this->priority;

        if ($percentageRemaining < 10) {
            $newPriority = 'critical';
        } elseif ($percentageRemaining < 25) {
            $newPriority = 'high';
        } elseif ($percentageRemaining < 50) {
            $newPriority = 'medium';
        }

        if ($newPriority !== $this->priority) {
            $this->priority = $newPriority;
            $this->save();
        }
    }

    /**
     * Get the progress percentage for template tasks
     */
    public function getProgressAttribute(): int
    {
        if (in_array($this->status, ['completed', 'cancelled'])) return 100;

        // If it has children (subtasks or instances), calculate aggregate progress
        if ($this->children()->exists()) {
            $totalCount = $this->children()->count();
            if ($totalCount === 0) return 0;

            $totalProgress = $this->children()->sum('progress_percentage');
            return (int) round($totalProgress / $totalCount);
        }

        // For individual tasks, return the manual progress percentage
        // If status is completed, it should be 100 anyway, but we return the column value
        return (int) ($this->attributes['progress_percentage'] ?? ($this->status === 'completed' ? 100 : 0));
    }

    /**
     * Get the associated forum thread.
     */
    public function forumThread(): HasOne
    {
        return $this->hasOne(ForumThread::class);
    }

    public function privateNotes()
    {
        return $this->hasMany(TaskPrivateNote::class);
    }

    /**
     * Get the private note for the current user.
     */
    public function currentPrivateNote()
    {
        return $this->hasOne(TaskPrivateNote::class)->where('user_id', auth()->id());
    }

    /**
     * Get the CSS class for Frappe Gantt based on Eisenhower quadrant
     */
    public function getGanttColorClass(): string
    {
        $quadrant = $this->getQuadrant($this);
        return "gantt-q{$quadrant}";
    }

    // Scopes
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
            ->where('status', '!=', 'completed');
    }

    public function scopeVisibleTo($query, $user, $isManager = false)
    {
        // Safety check: If no user is provided, the task is invisible by default.
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Ensure we are working with a builder to avoid cloning issues on Relation objects.
        $builder = $query instanceof \Illuminate\Database\Eloquent\Relations\Relation ? $query->getQuery() : $query;

        return $builder->where(function ($q) use ($user, $isManager) {
            // Política de privacidad:
            // - 'public': lo ve todo el equipo
            // - 'semi-private' o 'semiprivate': creador + asignados
            // - 'private' o NULL: solo creador (nada más)
            if ($isManager) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($template) {
                      $template->where('is_template', true)
                               ->where('visibility', 'public');
                  })
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere('assigned_user_id', $user->id)
                  ->orWhere(function ($semi) use ($user) {
                      $semi->whereIn('visibility', ['semi-private', 'semiprivate'])
                           ->where(function ($assigned) use ($user) {
                               $assigned->where('assigned_user_id', $user->id)
                                        ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                                        ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                           });
                  });
            } else {
                // Miembros: solo ven público sin asignados, o si son creador/assignado
                $q->where(function ($unassigned) {
                    $unassigned->whereNull('assigned_user_id')
                               ->whereDoesntHave('assignedTo')
                               ->whereDoesntHave('assignedGroups')
                               ->where('visibility', 'public');
                })
                ->orWhere('created_by_id', $user->id)
                ->orWhere('assigned_user_id', $user->id)
                ->orWhere(function ($semi) use ($user) {
                    $semi->whereIn('visibility', ['semi-private', 'semiprivate'])
                         ->where(function ($assigned) use ($user) {
                             $assigned->where('assigned_user_id', $user->id)
                                      ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                                      ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                         });
                })
                ->orWhereHas('assignedTo', fn($sub) => $sub->where('users.id', $user->id))
                ->orWhereHas('assignedGroups', fn($sub) => $sub->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            }
        });
    }

    /**
     * Scope for "What I should be working on or managing right now".
     * This handles the hierarchy to avoid showing both master and instance.
     */
    public function scopeOperationalFor($query, $user, Team $team, $includeFuture = false)
    {
        $isManager = $team->isManager($user);

        $query->where(function ($main) use ($user, $isManager) {
            if ($isManager) {
                // GESTIÓN (Managers/Coordinators): Ve el esqueleto (Plantillas y Raíces)
                // Evitamos traer instancias que no estén asignadas a él como filas principales,
                // ya que estas se verán dentro de sus respectivos Planes Maestros.
                $main->where(function($q) {
                    $q->whereNull('parent_id')
                      ->orWhere('is_template', true);
                });

                // DEDUPLICACIÓN EN GESTIÓN: Si el manager tiene una instancia propia, 
                // priorizamos ver el Plan Maestro (donde puede gestionar todo) y evitamos 
                // ver la instancia suelta arriba para no triplicar.
                $main->where(function($q) use ($user) {
                    $q->where('is_template', true)
                      ->orWhereNull('assigned_user_id')
                      ->orWhere('assigned_user_id', '!=', $user->id)
                      ->orWhereNull('parent_id'); // SIEMPRE ver tareas raíz, aunque estén asignadas a mí
                });
            } else {
                // MIEMBRO (Contexto Ejecución): Ve su trabajo asignado Y las tareas puras (sin asignar a nadie)
                // Permitimos ver plantillas y tareas autoprogramables si el usuario es creador o está asignado explícitamente.
                $main->where(function ($q) use ($user) {
                    $q->where('assigned_user_id', $user->id)
                      ->orWhereHas('assignedTo', fn ($as) => $as->where('users.id', $user->id))
                      ->orWhereHas('assignedGroups', fn ($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)))
                      ->orWhere(function ($own) use ($user) {
                          $own->where('created_by_id', $user->id)
                              ->whereNull('parent_id');
                      })
                      ->orWhere(function ($unassigned) {
                          $unassigned->where('is_template', false)
                                     ->whereNull('assigned_user_id')
                                     ->whereDoesntHave('assignedTo')
                                     ->whereDoesntHave('assignedGroups')
                                     ->where('visibility', 'public');
                      });
                });

                // DEDUPLICACIÓN EN EJECUCIÓN (Miembro): Si ve la hija, ocultamos el padre
                // Reforzamos para que compruebe tanto asignación directa como pivot en los hijos
                $main->whereDoesntHave('children', function ($q) use ($user) {
                    $q->where(function($sub) use ($user) {
                        $sub->where('assigned_user_id', $user->id)
                            ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', $user->id))
                            ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                    });
                });
            }
        });

        if (!$includeFuture) {
            $query->where(function ($q) {
                $q->whereNull('scheduled_date')
                  ->orWhere('scheduled_date', '<=', now())
                  ->orWhere('metadata->is_occurrence', true);
            });
        }

        return $query;
    }

    /**
     * Specialized scope for focused views (Kanban/Matrix).
     * Filters for actionable items and applies deduplication for managers.
     */
    public function scopeFocusedFor($query, $user, Team $team, $includeFuture = false)
    {
        $userId = $user->id;

        $query->where('is_template', false) // NUNCA mostrar planes maestros en Vistas Enfocadas (Matrix/Kanban)
            ->whereDoesntHave('children') // ELIMINAR FANTASMAS: Solo mostrar tareas finales (hojas), no contenedores u ocurrencias con hijos
            ->where(function ($q) use ($userId) {
                // ENFOQUE SIEMPRE EN EJECUCIÓN: Ver lo que tengo asignado, raíces creadas por mí, o tareas sin asignar (públicas)
                $q->where('assigned_user_id', $userId)
                  ->orWhereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId))
                  ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                  ->orWhere(function($roots) use ($userId) {
                      $roots->whereNull('parent_id')
                            ->where('created_by_id', $userId);
                  })
                  ->orWhere(function ($unassigned) {
                      $unassigned->whereNull('assigned_user_id')
                                 ->whereDoesntHave('assignedTo')
                                 ->whereDoesntHave('assignedGroups')
                                 ->where('visibility', 'public');
                  });
            });

        if (!$includeFuture) {
            $query->where(function ($q) {
                $q->whereNull('scheduled_date')
                  ->orWhere('scheduled_date', '<=', now())
                  ->orWhere('metadata->is_occurrence', true);
            });
        }

        return $query;
    }

    /**
     * Specialized scope for the Kanban board.
     * Legacy wrapper for scopeFocusedFor.
     */
    public function scopeOperationalForKanban($query, $user, $team, $includeFuture = false)
    {
        // EL KANBAN ES SAGRADO: Solo tareas finales (sin hijos) y que no sean plantillas maestras.
        // Aplicamos un filtro de "túnel" para ignorar cualquier otro orWhere de scopes anteriores.
        return $query->where(function($q) {
                $q->whereDoesntHave('children')
                  ->where('is_template', false);
            })
            ->where(function($q) use ($user, $team, $includeFuture) {
                 $q->focusedFor($user, $team, $includeFuture);
            });
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', '!=', 'completed');
    }
    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    /**
     * Synchronize the Kanban column based on current progress and status.
     */
    public function syncKanbanColumn(): void
    {
        $team = $this->team;

        // Fallback: If relationship is not loaded but team_id exists, try to find it
        if (!$team && $this->team_id) {
            $team = Team::find($this->team_id);
        }

        if (!$team) {
            return;
        }

        $currentProgress = (int)$this->progress;
        
        $expectedTypes = [];
        if ($currentProgress === 100) {
            $expectedTypes = ['done'];
        } elseif ($currentProgress === 0) {
            $expectedTypes = ['todo'];
        } else {
            $expectedTypes = ['in_progress', 'custom'];
        }

        $currentColumn = \App\Models\KanbanColumn::find($this->kanban_column_id);

        if (!$currentColumn || !in_array($currentColumn->type, $expectedTypes)) {
            $typeToAssign = $currentProgress === 100 ? 'done' : ($currentProgress === 0 ? 'todo' : 'in_progress');
            
            $defaultColumn = $team->kanbanColumns()
                ->where('type', $typeToAssign)
                ->orderBy('order_index')
                ->first();

            if ($defaultColumn && $this->kanban_column_id !== $defaultColumn->id) {
                $this->kanban_column_id = $defaultColumn->id;
                $this->saveQuietly();
            }
        }
    }

    /**
     * Iteratively generate occurrences until the next one falls outside the wakeup threshold.
     * This brings the task up to date with the current time and lead settings.
     */
    public function autoWakeup(): void
    {
        if (!$this->is_autoprogrammable) return;

        $maxIterations = 50; // Safety brake
        $iterations = 0;

        while ($this->is_autoprogrammable && $iterations < $maxIterations) {
            $settings = $this->autoprogram_settings;
            $nextAt = isset($settings['next_occurrence_at']) ? \Carbon\Carbon::parse($settings['next_occurrence_at']) : ($this->scheduled_date ? $this->scheduled_date->copy() : now());
            
            // SCENARIO 1: The task is OVERDUE or TODAY. 
            // We generate it and continue the loop to keep catching up.
            if (now()->greaterThanOrEqualTo($nextAt)) {
                $this->generateOccurrences();
                $this->refresh();
                $iterations++;
            } 
            // SCENARIO 2: The task is in the FUTURE. 
            // We only generate ONE if it's within the Lead Time, then ALWAYS STOP.
            else {
                $leadValue = (int)($settings['lead_value'] ?? 0);
                $leadUnit = $settings['lead_unit'] ?? 'days';
                
                $leadThreshold = $nextAt->copy();
                switch ($leadUnit) {
                    case 'hours': $leadThreshold->subHours($leadValue); break;
                    case 'days': $leadThreshold->subDays($leadValue); break;
                    case 'weeks': $leadThreshold->subWeeks($leadValue); break;
                    case 'months': $leadThreshold->subMonths($leadValue); break;
                }

                if (now()->greaterThanOrEqualTo($leadThreshold)) {
                    $this->generateOccurrences();
                }
                
                // Break the loop: we don't want to calculate the 2nd, 3rd, 4th future task yet.
                break; 
            }
        }
    }

    /**
     * Generate a single occurrence based on autoprogramming settings.
     */
    public function generateOccurrences(): void
    {
        $settings = $this->autoprogram_settings;
        $frequency = $settings['frequency'] ?? 'daily';
        $interval = (int)($settings['interval'] ?? 1);
        $limitType = $settings['limit_type'] ?? 'count';
        $limitValue = $settings['limit_value'] ?? 1;
        $sequential = $settings['sequential'] ?? false;
        $skipWeekends = $settings['skip_weekends'] ?? false;
        $leadValue = (int)($settings['lead_value'] ?? 7);
        $leadUnit = $settings['lead_unit'] ?? 'days';

        $lastOccurrence = $this->children()->whereNotNull('scheduled_date')->orderBy('scheduled_date', 'desc')->first();
        
        // If we already reached the limit based on count
        $occurrenceCount = $this->children()->where('metadata->is_occurrence', true)->count();
        if (!$settings) return;

        // 1. Determine the target date for the new occurrence
        $lastOccurrence = $this->children()->orderBy('scheduled_date', 'desc')->first();
        if (!$lastOccurrence) {
            $baseDate = $this->scheduled_date ? $this->scheduled_date->copy() : now();
        } else {
            $baseDate = $lastOccurrence->scheduled_date->copy();
        }

        // Calculate the actual date of the occurrence to create
        $targetDate = $this->calculateNextOccurrenceDate($baseDate, $settings, !$lastOccurrence);

        // 2. Prevent duplication
        if ($this->children()->whereDate('scheduled_date', $targetDate->toDateString())->exists()) {
            $this->updateNextOccurrenceAt($targetDate, $settings);
            return;
        }

        // 3. Create the occurrence (The child)
        $occurrence = $this->replicate(['is_autoprogrammable', 'autoprogram_settings', 'status', 'progress_percentage', 'uuid', 'google_task_id', 'google_calendar_event_id']);
        $occurrence->parent_id = $this->id;
        $occurrence->is_autoprogrammable = false;
        $occurrence->autoprogram_settings = null;
        $occurrence->status = 'pending';
        $occurrence->progress_percentage = 0;
        $occurrence->scheduled_date = $targetDate;
        
        // Mantain the same duration as the original task
        if ($this->scheduled_date && $this->due_date) {
            $duration = $this->scheduled_date->diffInMinutes($this->due_date);
            $occurrence->due_date = $targetDate->copy()->addMinutes($duration);
        }
        
        $occurrence->metadata = array_merge($occurrence->metadata ?? [], ['is_occurrence' => true]);
        $occurrence->save();

        // 4. Inherit Assignments
        foreach ($this->assignments as $assignment) {
            $occurrence->assignments()->create([
                'user_id' => $assignment->user_id,
                'group_id' => $assignment->group_id,
                'assigned_by_id' => $assignment->assigned_by_id,
            ]);
        }

        // 5. Handle Template (Distributed Mode) recursive logic
        if ($this->is_template) {
            $this->spawnInstancesForOccurrence($occurrence);
        }

        // 6. Advance the Master's pointer to the NEXT one
        $this->updateNextOccurrenceAt($targetDate, $settings);
    }

    protected function calculateNextOccurrenceDate($baseDate, $settings, $isFirst = false)
    {
        // If it's the very first one, we use the base date itself (the start of the cycle)
        if ($isFirst) return $baseDate->copy();

        $frequency = $settings['frequency'] ?? 'daily';
        $interval = (int)($settings['interval'] ?? 1);
        $nextDate = $baseDate->copy();

        switch ($frequency) {
            case 'daily':
                $nextDate->addDays($interval);
                break;
            case 'weekly':
                if (!empty($settings['days'])) {
                    $days = collect($settings['days'])->map(fn($d) => (int)$d)->sort();
                    $currentDay = $nextDate->dayOfWeekIso;
                    $nextDay = $days->first(fn($d) => $d > $currentDay);

                    if ($nextDay) {
                        $nextDate->addDays($nextDay - $currentDay);
                    } else {
                        $nextDate->addWeeks($interval);
                        $nextDate->setISODate($nextDate->year, $nextDate->weekOfYear, $days->first());
                    }
                } else {
                    $nextDate->addWeeks($interval);
                }
                break;
            case 'monthly':
                $monthlyType = $settings['monthly_type'] ?? 'date';
                if ($monthlyType === 'ordinal') {
                    $ordinal = $settings['monthly_ordinal'] ?? 'first';
                    $day = $settings['monthly_day'] ?? 'monday';
                    // Example: "first monday of +1 months"
                    $nextDate->modify("{$ordinal} {$day} of +{$interval} months");
                } else {
                    $nextDate->addMonths($interval);
                }
                break;
            case 'expression':
                $expression = $settings['expression'] ?? '';
                if (!empty($expression)) {
                    try {
                        $nextDate->modify($expression);
                    } catch (\Exception $e) {
                        // Fallback if invalid expression
                        $nextDate->addDays($interval);
                    }
                }
                break;
        }

        return $nextDate;
    }

    protected function updateNextOccurrenceAt($currentOccurrenceDate, $settings)
    {
        $nextValidDate = $this->calculateNextOccurrenceDate($currentOccurrenceDate, $settings, false);
        $settings['next_occurrence_at'] = $nextValidDate->toDateTimeString();
        $this->update(['autoprogram_settings' => $settings]);
    }

    /**
     * Helper to spawn individual instances for a specific occurrence.
     */
    protected function spawnInstancesForOccurrence(Task $occurrence): void
    {
        if (!$this->is_template) {
            $assignments = $this->assignments()->get();
            foreach ($assignments as $assignment) {
                $occurrence->assignments()->create([
                    'user_id' => $assignment->user_id,
                    'group_id' => $assignment->group_id,
                    'assigned_by_id' => $assignment->assigned_by_id,
                ]);
            }
            return;
        }

        $assignments = $this->assignments()->get();
        $userIds = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->user_id) {
                $userIds->push($assignment->user_id);
            } elseif ($assignment->group_id) {
                $group = Group::find($assignment->group_id);
                if ($group) {
                    $userIds = $userIds->merge($group->users->pluck('id'));
                }
            }
        }

        $userIds->push($this->created_by_id);
        $uniqueUserIds = $userIds->unique();

        foreach ($uniqueUserIds as $userId) {
            $occurrence->children()->create([
                'team_id' => $occurrence->team_id,
                'title' => $occurrence->title,
                'description' => $occurrence->description,
                'priority' => $occurrence->priority,
                'urgency' => $occurrence->urgency,
                'status' => 'pending',
                'scheduled_date' => $occurrence->scheduled_date,
                'due_date' => $occurrence->due_date,
                'original_due_date' => $occurrence->due_date,
                'created_by_id' => $occurrence->created_by_id,
                'parent_id' => $occurrence->id,
                'is_template' => false,
                'assigned_user_id' => $userId,
                'expediente_id' => $occurrence->expediente_id,
                'visibility' => 'private',
            ]);
        }
    }

    // Time Tracking Relationships
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Get total time spent on this task and its children in seconds.
     */
    public function totalTrackedSeconds(): int
    {
        // Own logs
        $ownSeconds = (int) $this->timeLogs()->whereNotNull('end_at')->get()
            ->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));

        // Children logs (for template/parent tasks)
        $childrenSeconds = 0;
        if ($this->children()->exists()) {
             // Efficiently calculate time from all descendants
             $childrenIds = $this->children()->pluck('id');
             $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)->whereNotNull('end_at')->get();
             $childrenSeconds = (int) $childrenLogs->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));
        }

        return $ownSeconds + $childrenSeconds;
    }

    /**
     * Get human-readable total time (e.g. 2h 30m).
     */
    public function totalTrackedTimeHuman(): string
    {
        $seconds = $this->totalTrackedSeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Get time tracked by the CURRENT USER today on this task in seconds.
     */
    public function trackedTimeTodaySeconds(): int
    {
        return (int) $this->timeLogs()
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', now()->startOfDay())
            ->whereNotNull('end_at')
            ->get()
            ->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));
    }

    /**
     * Get human-readable time tracked by CURRENT USER today.
     */
    public function trackedTimeTodayHuman(): string
    {
        $seconds = $this->trackedTimeTodaySeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($hours == 0 && $minutes == 0) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Get aggregate time tracked today by ALL USERS on this task and its children.
     */
    public function totalTrackedTimeTodaySeconds(): int
    {
        $own = (int) $this->timeLogs()
            ->where('created_at', '>=', now()->startOfDay())
            ->get()
            ->sum(fn($log) => $log->end_at ? max(0, $log->start_at->diffInSeconds($log->end_at, false)) : max(0, $log->start_at->diffInSeconds(now(), false)));

        $childrenSeconds = 0;
        if ($this->children()->exists()) {
            $childrenIds = $this->children()->pluck('id');
            $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)
                ->where('created_at', '>=', now()->startOfDay())
                ->get();
            $childrenSeconds = (int) $childrenLogs->sum(fn($log) => $log->end_at ? max(0, $log->start_at->diffInSeconds($log->end_at, false)) : max(0, $log->start_at->diffInSeconds(now(), false)));
        }

        return $own + $childrenSeconds;
    }

    /**
     * Get human-readable aggregate time tracked today.
     */
    public function totalTrackedTimeTodayHuman(): string
    {
        $seconds = $this->totalTrackedTimeTodaySeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
    /**
     * Get all attachments associated with this task, its parent, and its children.
     */
    public function getAllAttachmentsAttribute()
    {
        return $this->attachments()
            ->with('attachable') // eager load polymorphic relationship
            ->get();
    }
    /**
     * Notify creator and coordinators about a task event.
     */
    public function notifyCreatorAndCoordinators($notification)
    {
        $recipients = collect();

        // 1. Add Creator
        if ($this->creator && $this->creator->id !== auth()->id()) {
            $recipients->push($this->creator);
        }

        // 2. Add Coordinators (filtered by visibility/involvement)
        $coordinators = $this->team->coordinators()
            ->where('users.id', '!=', auth()->id())
            ->get();
            
        $filteredCoordinators = $coordinators->filter(function ($coordinator) {
            if ($this->visibility === 'public') {
                return true;
            }
            // For non-public activities, check if coordinator is involved
            if ($this->created_by_id === $coordinator->id) return true;
            if ($this->assigned_user_id === $coordinator->id) return true;
            if ($this->assignedTo->contains('id', $coordinator->id)) return true;
            if ($this->assignedGroups->filter(fn($g) => $g->users->contains('id', $coordinator->id))->isNotEmpty()) return true;
            
            return false;
        });
        
        $recipients = $recipients->merge($filteredCoordinators)->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /**
     * Notify coordinators if the task is completed and meets specific criteria.
     */
    public function notifyCoordinatorsIfCompleted()
    {
        if ($this->status !== 'completed') {
            return;
        }

        $actor = auth()->user() ?? $this->assignedUser ?? $this->creator;
        $actorId = $actor ? $actor->id : null;
        
        $recipients = collect();

        // 1. TAREAS PRIVADAS ('private' o NULL)
        // Solo creador
        if ($this->visibility === 'private' || is_null($this->visibility)) {
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
        } 
        // 2. TAREAS SEMIPRIVADAS ('semi-private' o 'semiprivate')
        // Creador + asignados
        elseif (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
            if ($this->assignedUser && $this->assignedUser->id !== $actorId) {
                $recipients->push($this->assignedUser);
            }
        } 
        // 3. TAREAS PÚBLICAS ('public')
        // Si es Plan Maestro o supervisada por coordinador, se avisa a coordinadores. Si no, solo al creador si la completó otro.
        else {
            if ($this->is_template || $this->team->isCoordinator($this->creator)) {
                $coordinators = $this->team->coordinators()
                    ->when($actorId, fn($q) => $q->where('users.id', '!=', $actorId))
                    ->get();
                $recipients = $recipients->merge($coordinators);
            } else {
                if ($this->creator && $this->creator->id !== $actorId) {
                    $recipients->push($this->creator);
                }
            }
        }

        $recipients = $recipients->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new \App\Notifications\TaskCompletedNotification($this, $actor));
        }
    }
    /**
     * Get the ratings for the task.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(TaskRating::class);
    }

    /**
     * Recompute and cache the average quality score based on votes.
     */
    public function updateQualityCache(): void
    {
        $this->avg_quality_score = $this->ratings()->avg('score') ?: 0;
        $this->saveQuietly();
    }
}
