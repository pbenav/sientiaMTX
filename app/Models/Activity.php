<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\HandlesEisenhowerMatrix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Modelo base universal de Actividad.
 *
 * Representa cualquier tipo de contenido estructurado dentro del sistema:
 * task, document, note, link, decision, meeting, reminder, custom...
 *
 * Todos los tipos comparten esta infraestructura:
 *   - Etiquetas, notas, historial, asignaciones, adjuntos, calificaciones
 *   - Posición en Kanban, Matriz Eisenhower y Gantt
 *   - Visibilidad, prioridad, fechas y estado flexible por tipo
 *
 * Para añadir un tipo nuevo: crear un modelo en app/Models/Activities/,
 * añadirlo a SUBTYPES y crear su partial de vista. Nada más.
 */
class Activity extends Model
{
    use HasFactory, SoftDeletes, HasUuid, HandlesEisenhowerMatrix;

    // ─── Tipos registrados ────────────────────────────────────────────────────
    public const SUBTYPES = [
        'task'      => \App\Models\Activities\TaskActivity::class,
        'document'  => \App\Models\Activities\DocumentActivity::class,
        'note'      => \App\Models\Activities\NoteActivity::class,
        'link'      => \App\Models\Activities\LinkActivity::class,
        'decision'  => \App\Models\Activities\DecisionActivity::class,
        'meeting'   => \App\Models\Activities\MeetingActivity::class,
        'reminder'  => \App\Models\Activities\ReminderActivity::class,
    ];

    // Tipos que pueden aparecer en el Kanban
    public const KANBAN_TYPES = ['task', 'meeting', 'reminder'];

    // Tipos que pueden aparecer en la Matriz Eisenhower
    public const MATRIX_TYPES = ['task', 'meeting', 'reminder'];

    // Tipos que aparecen en el Gantt (cualquiera con due_date)
    public const GANTT_TYPES  = ['task', 'meeting', 'reminder'];

    // ─── Atributos ────────────────────────────────────────────────────────────
    protected $table = 'activities';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_id',
        'parent_id',
        'expediente_id',
        'type',
        'title',
        'description',
        'status',
        'metadata',
        'visibility',
        'due_date',
        'scheduled_date',
        'original_due_date',
        'priority',
        'auto_priority',
        'progress_percentage',
        'kanban_column_id',
        'kanban_order',
        'matrix_order',
        'is_archived',
        'is_template',
        'google_task_id',
        'google_task_list_id',
        'google_calendar_event_id',
        'google_calendar_id',
        'google_synced_at',
    ];

    protected $casts = [
        'status'               => 'array',
        'metadata'             => 'array',
        'due_date'             => 'datetime',
        'scheduled_date'       => 'datetime',
        'original_due_date'    => 'datetime',
        'google_synced_at'     => 'datetime',
        'is_archived'          => 'boolean',
        'is_template'          => 'boolean',
        'auto_priority'        => 'boolean',
        'progress_percentage'  => 'integer',
    ];

    // ─── Relaciones principales ───────────────────────────────────────────────
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_id')->orderBy('created_at');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_id')->where('is_template', false)->orderBy('title');
    }

    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    // ─── Infraestructura compartida ───────────────────────────────────────────
    public function tags(): HasMany
    {
        return $this->hasMany(ActivityTag::class, 'activity_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ActivityHistory::class, 'activity_id')->latest();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ActivityAssignment::class, 'activity_id');
    }

    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_assignments', 'activity_id', 'user_id')
            ->withPivot('assigned_at', 'assigned_by_id', 'completed_at')
            ->withTimestamps()
            ->orderBy('name');
    }

    // Compatibilidad legacy: devuelve el primer/principal usuario asignado a la actividad
    public function assignedUser()
    {
        return $this->hasOneThrough(
            User::class,
            ActivityAssignment::class,
            'activity_id', // Foreign key on activity_assignments table
            'id',          // Foreign key on users table
            'id',          // Local key on activities table
            'user_id'      // Local key on activity_assignments table
        );
    }

    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'activity_assignments', 'activity_id', 'group_id')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ActivityRating::class, 'activity_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ActivityNote::class, 'activity_id')->latest();
    }

    public function privateNoteFor(int $userId): ?ActivityNote
    {
        return $this->hasOne(ActivityNote::class, 'activity_id')
            ->where('user_id', $userId)
            ->where('visibility', 'private')
            ->first();
    }

    /**
     * Get the private note for the current user (Task compat).
     */
    public function currentPrivateNote()
    {
        return $this->hasOne(\App\Models\TaskPrivateNote::class, 'task_id')->where('user_id', auth()->id());
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityAttachment::class, 'activity_id');
    }

    // ─── Relaciones de especialidades y servicios (Task compat layer) ─────────
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'activity_skills', 'activity_id', 'skill_id');
    }

    public function getSkillIdAttribute(): ?int
    {
        return data_get($this->metadata, 'skill_id') ?? ($this->relationLoaded('skills') ? $this->skills->first()?->id : null);
    }

    public function skill(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
    }

    public function getServiceIdAttribute(): ?int
    {
        return data_get($this->metadata, 'service_id');
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function isBlockedByService(): bool
    {
        return $this->service_id && $this->service && $this->service->status === 'down';
    }

    // ─── Subtipo dinámico ─────────────────────────────────────────────────────
    /**
     * Resuelve la clase de subtipo para este tipo de actividad.
     * Permite tratar la actividad como su subtipo específico (TaskActivity, etc.)
     */
    public function isInstance(): bool
    {
        return !empty($this->parent_id) && !$this->is_template;
    }

    public function getIsAutoprogrammableAttribute(): bool
    {
        return data_get($this->metadata, 'is_autoprogrammable', false);
    }

    public function getPrivacyLevelAttribute(): string
    {
        return $this->visibility ?? 'public';
    }

    public function getAvgQualityScoreAttribute(): float
    {
        return data_get($this->metadata, 'avg_quality_score', 0);
    }

    /**
     * Devuelve el primer usuario asignado individualmente (compat. con vista de tareas).
     * Si se ha cargado eager loading de assignedTo, lo usa sin hacer nueva query.
     */
    public function getAssignedUserAttribute(): ?\App\Models\User
    {
        if ($this->relationLoaded('assignedTo')) {
            return $this->assignedTo->first();
        }
        return $this->assignedTo()->first();
    }

    /**
     * Helper paramétrico para factory pattern (Activity polymorphism).
     */
    public function asSubtype(): static
    {
        $class = self::SUBTYPES[$this->type] ?? static::class;
        $model = new $class();
        $model->setRawAttributes($this->getRawOriginal(), true);
        $model->exists = $this->exists;
        return $model;
    }

    // ─── Estado y progreso ────────────────────────────────────────────────────
    public function getStatusValueAttribute(): ?string
    {
        return $this->status['value'] ?? null;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status_value, ['completed', 'done', 'approved', 'triggered']);
    }

    public function isPending(): bool
    {
        return in_array($this->status_value, ['pending', 'draft', 'scheduled']);
    }

    // ─── Visibilidad ──────────────────────────────────────────────────────────
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->visibility === 'public') return true;
        if ($this->created_by_id === $user->id) return true;

        return $this->assignedTo->contains('id', $user->id)
            || $this->assignedGroups->filter(fn($g) => $g->users->contains('id', $user->id))->isNotEmpty();
    }

    // ─── Compatibilidad con partial timer (Task compat layer) ─────────────────
    /**
     * Relación con TimeLog apuntando al task_id (columna polivalente).
     */
    public function timeLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TimeLog::class, 'task_id', 'id');
    }

    public function totalTrackedSeconds(): int
    {
        // Own logs
        $ownSeconds = (int) $this->timeLogs()->whereNotNull('end_at')->get()
            ->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at));

        // Children logs
        $childrenSeconds = 0;
        if ($this->children()->exists()) {
             $childrenIds = $this->children()->pluck('id');
             $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)->whereNotNull('end_at')->get();
             $childrenSeconds = (int) $childrenLogs->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at));
        }

        return $ownSeconds + $childrenSeconds;
    }

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
     * Get aggregate time tracked today by ALL USERS on this activity and its children.
     */
    public function totalTrackedTimeTodaySeconds(): int
    {
        $own = (int) $this->timeLogs()
            ->where('created_at', '>=', now()->startOfDay())
            ->get()
            ->sum(fn($log) => $log->end_at ? $log->start_at->diffInSeconds($log->end_at) : $log->start_at->diffInSeconds(now()));

        $childrenSeconds = 0;
        if ($this->children()->exists()) {
            $childrenIds = $this->children()->pluck('id');
            $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)
                ->where('created_at', '>=', now()->startOfDay())
                ->get();
            $childrenSeconds = (int) $childrenLogs->sum(fn($log) => $log->end_at ? $log->start_at->diffInSeconds($log->end_at) : $log->start_at->diffInSeconds(now()));
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
     * Actualiza la prioridad de forma automática basándose en el tiempo restante.
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
     * Compat con Task: devuelve el % de progreso.
     * Para Activity usa progress_percentage directamente.
     */
    public function getProgressAttribute(): int
    {
        if (in_array($this->status_value, ['completed', 'done', 'approved', 'triggered'])) return 100;
        return (int) ($this->progress_percentage ?? 0);
    }

    /**
     * Compat con Task: devuelve el ID del primer usuario asignado.
     */
    public function getAssignedUserIdAttribute(): ?int
    {
        if ($this->relationLoaded('assignedTo')) {
            return $this->assignedTo->first()?->id;
        }
        return $this->assignedTo()->value('users.id');
    }

    /**
     * Compat con Task (Matriz de Eisenhower): gestiona la urgencia en metadata.
     */
    public function getUrgencyAttribute(): string
    {
        $meta = $this->metadata ?? [];
        return $meta['urgency'] ?? 'medium';
    }

    public function setUrgencyAttribute(string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['urgency'] = $value;
        $this->metadata = $meta;
    }


    public function isInKanban(): bool
    {
        return in_array($this->type, self::KANBAN_TYPES) && $this->kanban_column_id !== null;
    }

    public function isInMatrix(): bool
    {
        return in_array($this->type, self::MATRIX_TYPES) && $this->matrix_order !== null;
    }

    public function isInGantt(): bool
    {
        return in_array($this->type, self::GANTT_TYPES) && $this->due_date !== null;
    }

    public function getGanttColorClass(): string
    {
        return match($this->type) {
            'task'     => 'gantt-task',
            'meeting'  => 'gantt-meeting',
            'document' => 'gantt-document',
            'reminder' => 'gantt-reminder',
            'decision' => 'gantt-decision',
            default    => 'gantt-default',
        };
    }

    // ─── UI helpers ───────────────────────────────────────────────────────────
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'task'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            'document' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
            'note'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
            'link'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>',
            'decision' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>',
            'meeting'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
            'reminder' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>',
            default    => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" /></svg>',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'task'     => __('Tarea'),
            'document' => __('Documento'),
            'note'     => __('Nota'),
            'link'     => __('Enlace'),
            'decision' => __('Decisión'),
            'meeting'  => __('Reunión'),
            'reminder' => __('Recordatorio'),
            default    => __('Actividad'),
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->type) {
            'task'     => 'blue',
            'document' => 'orange',
            'note'     => 'yellow',
            'link'     => 'purple',
            'decision' => 'red',
            'meeting'  => 'green',
            'reminder' => 'pink',
            default    => 'gray',
        };
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfTypes($query, array $types)
    {
        return $query->whereIn('type', $types);
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereJsonDoesntContain('status->value', 'completed')
            ->whereJsonDoesntContain('status->value', 'cancelled');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeForKanban($query)
    {
        return $query->whereIn('type', self::KANBAN_TYPES);
    }

    public function scopeForMatrix($query)
    {
        return $query->whereIn('type', self::MATRIX_TYPES)->where('is_archived', false);
    }

    public function scopeForGantt($query)
    {
        return $query->whereIn('type', self::GANTT_TYPES)
            ->whereNotNull('due_date')
            ->where('is_archived', false);
    }

    public function scopeVisibleTo($query, User $user, bool $isManager = false)
    {
        if (!$user) return $query->whereRaw('1 = 0');

        $builder = $query instanceof \Illuminate\Database\Eloquent\Relations\Relation ? $query->getQuery() : $query;

        return $builder->where(function ($q) use ($user, $isManager) {
            // 1. GESTIÓN (Managers): Ven todo lo público Y todas las plantillas/esqueleto del equipo
            // Pero NO ven las actividades privadas de otros usuarios a menos que estén asignados a ellas
            if ($isManager) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($template) {
                      $template->where('is_template', true)
                               ->where('visibility', '!=', 'private');
                  })
                  ->orWhere('created_by_id', $user->id)
                  ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                  ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            } else {
                // 2. EJECUCIÓN (Miembros): "Al vuelo". Ven las tareas si:
                // - No tienen asignados y son explícitamente públicas
                // - O si ellos mismos son el creador
                // - O si están asignados directamente
                // - O si un grupo suyo está asignado
                $q->where(function ($unassigned) {
                    $unassigned->where('visibility', 'public')
                               ->whereDoesntHave('assignments')
                               ->whereNotExists(function ($sub) {
                                   $sub->select(\DB::raw(1))
                                       ->from('activity_task_mapping')
                                       ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                       ->whereColumn('activity_task_mapping.activity_id', 'activities.id');
                               });
                })
                ->orWhere('created_by_id', $user->id)
                ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            }
        });
    }

    public function scopeNotEphemeral($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('metadata->is_ephemeral')
              ->orWhere('metadata->is_ephemeral', false)
              ->orWhere('metadata->is_ephemeral', 'false')
              ->orWhere('metadata->is_ephemeral', '0');
        });
    }

    public function scopeFocusedFor($query, $user, Team $team, $includeFuture = false)
    {
        $userId = $user->id;

        $query->where('is_template', false)
            ->whereDoesntHave('children')
            ->where(function ($q) use ($userId) {
                $q->whereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId))
                  ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                  ->orWhere(function($roots) use ($userId) {
                      $roots->whereNull('parent_id')
                            ->where('created_by_id', $userId);
                  })
                  ->orWhere(function ($unassigned) {
                      $unassigned->where('visibility', 'public')
                                 ->whereDoesntHave('assignedTo')
                                 ->whereDoesntHave('assignedGroups')
                                 ->whereNotExists(function ($sub) {
                                     $sub->select(\DB::raw(1))
                                         ->from('activity_task_mapping')
                                         ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                         ->whereColumn('activity_task_mapping.activity_id', 'activities.id');
                                 });
                  })
                  ->orWhereExists(function ($sub) use ($userId) {
                      $sub->select(\DB::raw(1))
                          ->from('activity_task_mapping')
                          ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                          ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                          ->where(function ($a) use ($userId) {
                              $a->where('task_assignments.user_id', $userId)
                                ->orWhereExists(function ($g) use ($userId) {
                                    $g->select(\DB::raw(1))
                                      ->from('group_user')
                                      ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                                      ->where('group_user.user_id', $userId);
                                });
                          });
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

    public function scopeOperationalFor($query, $user, Team $team, $includeFuture = false)
    {
        $isManager = $team->isManager($user);
        $userId = $user->id;

        $query->where(function ($main) use ($userId, $isManager) {
            if ($isManager) {
                // GESTIÓN (Managers/Coordinators): Ve el esqueleto (Plantillas y Raíces)
                $main->where(function($q) {
                    $q->whereNull('parent_id')
                      ->orWhere('is_template', true);
                });

                // DEDUPLICACIÓN EN GESTIÓN: Si el manager tiene una instancia propia, 
                // priorizamos ver el Plan Maestro (donde puede gestionar todo) y evitamos 
                // ver la instancia suelta arriba para no triplicar.
                $main->where(function($q) use ($userId) {
                    $q->where('is_template', true)
                      ->orWhereDoesntHave('assignedTo')
                      ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', '!=', $userId))
                      ->orWhereNull('parent_id'); // SIEMPRE ver tareas raíz
                });
            } else {
                // MIEMBRO (Contexto Ejecución): Ve su trabajo asignado Y las tareas puras (sin asignar a nadie)
                $main->where(function ($q) use ($userId) {
                    $q->whereHas('assignedTo', fn ($as) => $as->where('users.id', $userId))
                      ->orWhereHas('assignedGroups', fn ($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                      ->orWhere(function ($own) use ($userId) {
                          $own->where('created_by_id', $userId)
                              ->whereNull('parent_id');
                      })
                      ->orWhere(function ($unassigned) {
                          $unassigned->where('is_template', false)
                                     ->whereDoesntHave('assignedTo')
                                     ->whereDoesntHave('assignedGroups')
                                     ->where('visibility', 'public');
                      });
                });

                // DEDUPLICACIÓN EN EJECUCIÓN (Miembro): Si ve la hija, ocultamos el padre
                $main->whereDoesntHave('children', function ($q) use ($userId) {
                    $q->where(function($sub) use ($userId) {
                        $sub->whereHas('assignedTo', fn($at) => $at->where('users.id', $userId))
                            ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)));
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

    public function scopeOperationalForKanban($query, $user, $team, $includeFuture = false)
    {
        return $query->where(function($q) {
                $q->whereDoesntHave('children')
                  ->where('is_template', false);
            })
            ->where(function($q) use ($user, $team, $includeFuture) {
                 $q->focusedFor($user, $team, $includeFuture);
            });
    }

    public function syncKanbanColumn(): void
    {
        $team = $this->team;
        if (!$team && $this->team_id) {
            $team = Team::find($this->team_id);
        }
        if (!$team) return;

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

    public function notifyCreatorAndCoordinators($notification)
    {
        $recipients = collect();

        // 1. Add Creator
        if ($this->creator && $this->creator->id !== auth()->id()) {
            $recipients->push($this->creator);
        }

        // 2. Add Coordinators
        $coordinators = $this->team->coordinators()
            ->where('users.id', '!=', auth()->id())
            ->get();
        
        $recipients = $recipients->merge($coordinators)->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    public function notifyCoordinatorsIfCompleted()
    {
        if ($this->status_value !== 'completed') return;

        $actor = auth()->user() ?? $this->assignedUser ?? $this->creator;
        $actorId = $actor ? $actor->id : null;
        
        $recipients = collect();

        if ($this->visibility === 'private') {
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
            if ($this->assignedUser && $this->assignedUser->id !== $actorId) {
                $recipients->push($this->assignedUser);
            }
        } elseif ($this->visibility === 'semiprivate') {
            if ($this->is_template) {
                $members = $this->team->members()->where('users.id', '!=', $actorId)->get();
                $recipients = $recipients->merge($members);
            } else {
                if ($this->creator && $this->creator->id !== $actorId) {
                    $recipients->push($this->creator);
                }
            }
        } else {
            if ($this->is_template || ($this->creator && $this->team->isCoordinator($this->creator))) {
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

    // ─── Helpers de Conversión y Deprecación ──────────────────────────────────
    public function isDeprecatedByConversion(): bool
    {
        return $this->is_archived && ($this->status_value === 'deprecated' || data_get($this->metadata, 'is_deprecated', false));
    }

    public function getConvertedToActivityAttribute(): ?Activity
    {
        $toUuid = data_get($this->metadata, 'converted_to_uuid');
        return $toUuid ? static::where('uuid', $toUuid)->first() : null;
    }

    public function getConvertedFromActivityAttribute(): ?Activity
    {
        $fromUuid = data_get($this->metadata, 'converted_from_uuid');
        return $fromUuid ? static::where('uuid', $fromUuid)->first() : null;
    }

    // ─── Adjuntos ──────────────────────────────────────────────────────────────
    public function getAllAttachmentsAttribute()
    {
        return $this->attachments()
            ->get();
    }
}

