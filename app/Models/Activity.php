<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use App\Traits\ActivityAccessors;
use App\Traits\ActivityConversion;
use App\Traits\HasUuid;
use App\Traits\HandlesEisenhowerMatrix;
use App\Traits\ActivityScopes;
use App\Traits\ActivityTracking;
use App\Traits\ActivityVisibility;
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
    use HasFactory, SoftDeletes, HasUuid, HandlesEisenhowerMatrix, ActivityScopes, ActivityAccessors, ActivityTracking, ActivityVisibility, ActivityConversion, \App\Traits\ActivityOccurrences;

    // ─── Tipos registrados ────────────────────────────────────────────────────
    public const SUBTYPES = [
        'task'      => \App\Models\Activities\TaskActivity::class,
        'document'  => \App\Models\Activities\DocumentActivity::class,
        'note'      => \App\Models\Activities\NoteActivity::class,
        'link'      => \App\Models\Activities\LinkActivity::class,
        'agreement'  => \App\Models\Activities\AgreementActivity::class,
        'meeting'   => \App\Models\Activities\MeetingActivity::class,
        'reminder'  => \App\Models\Activities\ReminderActivity::class,
    ];

    // Tipos que pueden aparecer en el Kanban (Flujo de trabajo / Estados)
    public const KANBAN_TYPES = ['task', 'agreement'];

    // Tipos que pueden aparecer en la Matriz Eisenhower (Priorización por urgencia/importancia)
    public const MATRIX_TYPES = ['task', 'agreement'];

    // Tipos que aparecen en el Gantt (Línea de tiempo / Fechas)
    public const GANTT_TYPES  = ['task', 'meeting', 'reminder', 'document', 'agreement'];

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

    public function skill(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
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


    public function isCompleted(): bool
    {
        return in_array($this->status_value, ['completed', 'done', 'approved', 'triggered', 'accepted', 'finished']);
    }

    public function isPending(): bool
    {
        return in_array($this->status_value, ['pending', 'draft', 'scheduled', 'proposed', 'todo']);
    }



    // ─── Compatibilidad con partial timer (Task compat layer) ─────────────────
    /**
     * Relación con TimeLog apuntando al task_id (columna polivalente).
     */
    public function timeLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TimeLog::class, 'task_id', 'id');
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
            'agreement' => 'gantt-decision',
            default    => 'gantt-default',
        };
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

        // 2. Add Coordinators (filtered by visibility/involvement)
        $coordinators = $this->team->coordinators()
            ->where('users.id', '!=', auth()->id())
            ->get();
            
        $filteredCoordinators = $coordinators->filter(function ($coordinator) {
            if ($this->visibility === 'public') {
                return true;
            }
            // 'semi-private': solo creador + asignados
            if (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
                if ($this->created_by_id === $coordinator->id) return true;
                if ($this->assignedTo->contains('id', $coordinator->id)) return true;
                if ($this->assignedGroups->filter(fn($g) => $g->users->contains('id', $coordinator->id))->isNotEmpty()) return true;
                return false;
            }
            // 'private' o NULL: solo creador
            return $this->created_by_id === $coordinator->id;
        });
        
        $recipients = $recipients->merge($filteredCoordinators)->unique('id');

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

        if ($this->visibility === 'private' || is_null($this->visibility)) {
            // Privada: solo creador
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
        } elseif (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
            // Semi-privada: creador + asignados
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
            if ($this->assignedUser && $this->assignedUser->id !== $actorId) {
                $recipients->push($this->assignedUser);
            }
        } else {
            // Pública: coordinadores del equipo
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

}

