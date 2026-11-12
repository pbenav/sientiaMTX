<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hilo de foro con soporte para biblioteca de conocimiento.
 *
 * Representa un hilo de discusión dentro de un equipo, vinculado
 * opcionalmente a una tarea. Los hilos sin tarea vinculada se
 * consideran parte de la "Biblioteca de Conocimiento".
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece el hilo
 * - user_id: ID del usuario que creó el hilo
 * - task_id: ID de la tarea asociada (null para biblioteca de conocimiento)
 * - title: Título del hilo
 * - is_pinned: Si el hilo está fijado en la parte superior
 * - is_locked: Si el hilo está bloqueado (sin nuevos mensajes)
 * - views: Número de vistas del hilo
 *
 * @property-read int $team_id
 * @property-read int $user_id
 * @property-read int|null $task_id
 * @property-read string $title
 * @property-read bool $is_pinned
 * @property-read bool $is_locked
 * @property-read int $views
 * @property-read bool $is_knowledge_library
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Task|null $task
 * @property-read \App\Models\Activity|null $activity
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ForumMessage> $messages
 *
 * @mixin Builder
 */
class ForumThread extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'task_id',
        'activity_id',
        'title',
        'is_pinned',
        'is_locked',
        'views',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Relación de pertenencia al equipo del hilo.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia al usuario que creó el hilo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia a la tarea asociada al hilo.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relación de pertenencia a la actividad asociada al hilo.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Relación uno-a-muchos con los mensajes del hilo.
     *
     * @return HasMany<\App\Models\ForumMessage, $this>
     */
    public function messages()
    {
        return $this->hasMany(ForumMessage::class);
    }

    /**
     * Scope: hilos huérfanos (sin tarea o actividad vinculada) = Biblioteca de Conocimiento.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrphaned($query)
    {
        return $query->whereNull('task_id')->whereNull('activity_id');
    }

    /**
     * Determina si este hilo es parte de la "Biblioteca de Conocimiento".
     *
     * @return bool True si el hilo no tiene tarea ni actividad vinculada
     */
    public function isKnowledgeLibrary(): bool
    {
        return is_null($this->task_id) && is_null($this->activity_id);
    }

    /**
     * Nombre del autor del hilo, con fallback seguro si el usuario es null.
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? __('Sistema');
    }

    /**
     * URL de la foto de perfil del autor del hilo, con fallback a avatar generado si el usuario es null.
     */
    public function getAuthorPhotoUrlAttribute(): string
    {
        return $this->user?->profile_photo_url
            ?? 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($this->author_name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Comprueba si un usuario tiene acceso para ver y participar en el hilo de discusión.
     * Respeta Deep Privacy, pero garantiza que cualquier usuario asignado a la actividad o tarea,
     * a cualquiera de sus subtareas/hijos, al padre, a actividades convertidas,
     * o como secretario de cita vinculada, tenga acceso completo.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Team|null $team
     * @return bool
     */
    public function canUserAccess(User $user, ?Team $team = null): bool
    {
        $team = $team ?? $this->team;
        if (!$team || $this->team_id !== $team->id) {
            return false;
        }

        // 1. Debe ser miembro del equipo o creador del equipo
        $isMember = $team->relationLoaded('members')
            ? $team->members->contains('id', $user->id)
            : $team->members()->where('users.id', $user->id)->exists();
        if (!$isMember && $team->created_by_id !== $user->id) {
            return false;
        }

        $isCoordinator = $team->isCoordinator($user);

        // 2. Si no está vinculado a actividad ni tarea (Biblioteca de Conocimiento / General del equipo)
        if (is_null($this->activity_id) && is_null($this->task_id)) {
            return true;
        }

        // 3. El autor original del hilo siempre tiene acceso
        if ($this->user_id === $user->id) {
            return true;
        }

        // 4. Si está vinculado a una Actividad
        if ($this->activity_id) {
            $activity = $this->activity()->withTrashed()->first();
            if (!$activity) {
                return true;
            }

            // Coordinadores ven todo excepto actividades estrictamente privadas creadas por otros
            if ($isCoordinator) {
                if ($activity->visibility !== 'private' || $activity->created_by_id === $user->id) {
                    return true;
                }
            }

            // Creador de la actividad
            if ($activity->created_by_id === $user->id) {
                return true;
            }

            // Si la política de la actividad le permite verla
            if ($user->can('view', $activity)) {
                return true;
            }

            // Scope visibleTo
            if (\App\Models\Activity::where('id', $activity->id)->withTrashed()->visibleTo($user, $isCoordinator)->exists()) {
                return true;
            }

            // Asignación directa o por grupo en la actividad
            if ($activity->assignments()->where('user_id', $user->id)->exists()) {
                return true;
            }
            if ($activity->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists()) {
                return true;
            }

            // Asignación en cualquier subtarea / actividad hija
            if ($activity->children()->where(function ($cq) use ($user) {
                $cq->where('created_by_id', $user->id)
                   ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', $user->id))
                   ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            })->exists()) {
                return true;
            }

            // Asignación en la actividad padre
            if ($activity->parent && (
                $activity->parent->created_by_id === $user->id ||
                $activity->parent->assignments()->where('user_id', $user->id)->exists() ||
                $activity->parent->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists()
            )) {
                return true;
            }

            // Asignación en actividades vinculadas por conversión (origen o destino)
            if ($activity->convertedFromActivity && (
                $activity->convertedFromActivity->created_by_id === $user->id ||
                $activity->convertedFromActivity->assignments()->where('user_id', $user->id)->exists() ||
                $activity->convertedFromActivity->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists()
            )) {
                return true;
            }
            if ($activity->convertedToActivity && (
                $activity->convertedToActivity->created_by_id === $user->id ||
                $activity->convertedToActivity->assignments()->where('user_id', $user->id)->exists() ||
                $activity->convertedToActivity->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists()
            )) {
                return true;
            }

            // Mapeo legacy de tareas
            $isLegacy = \DB::table('activity_task_mapping')
                ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                ->where('activity_task_mapping.activity_id', $activity->id)
                ->where(function ($a) use ($user) {
                    $a->where('task_assignments.user_id', $user->id)
                      ->orWhereExists(function ($g) use ($user) {
                          $g->select(\DB::raw(1))
                            ->from('group_user')
                            ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                            ->where('group_user.user_id', $user->id);
                      });
                })->exists();
            if ($isLegacy) {
                return true;
            }

            // Secretario/a de cita previa vinculada
            $appointment = $activity->relationLoaded('appointment')
                ? $activity->appointment
                : \App\Models\Appointment::where('activity_id', $activity->id)->first();
            if ($appointment && $user->isSecretaryOf($appointment->user_id, $team->id)) {
                return true;
            }
        }

        // 5. Si está vinculado a una Tarea (Legacy Task)
        if ($this->task_id) {
            $task = $this->task()->withTrashed()->first();
            if (!$task) {
                return true;
            }

            if ($isCoordinator) {
                if ($task->visibility !== 'private' || $task->created_by_id === $user->id) {
                    return true;
                }
            }

            if ($task->created_by_id === $user->id || $task->assigned_user_id === $user->id) {
                return true;
            }

            if ($user->can('view', $task)) {
                return true;
            }

            if (\App\Models\Task::where('id', $task->id)->withTrashed()->visibleTo($user, $isCoordinator)->exists()) {
                return true;
            }

            if ($task->assignedTo()->where('users.id', $user->id)->exists()) {
                return true;
            }
            if ($task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists()) {
                return true;
            }

            // Subtareas de la tarea
            if ($task->children()->where(function ($cq) use ($user) {
                $cq->where('created_by_id', $user->id)
                   ->orWhere('assigned_user_id', $user->id)
                   ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', $user->id))
                   ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            })->exists()) {
                return true;
            }

            // Tarea padre
            if ($task->parent && (
                $task->parent->created_by_id === $user->id ||
                $task->parent->assigned_user_id === $user->id ||
                $task->parent->assignedTo()->where('users.id', $user->id)->exists() ||
                $task->parent->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists()
            )) {
                return true;
            }

            // Si la tarea fue migrada a una actividad y el usuario está asignado a esa actividad
            $isMappedActivity = \DB::table('activity_task_mapping')
                ->where('task_id', $task->id)
                ->whereExists(function ($sub) use ($user) {
                    $sub->select(\DB::raw(1))
                        ->from('activity_assignments')
                        ->whereColumn('activity_assignments.activity_id', 'activity_task_mapping.activity_id')
                        ->where('activity_assignments.user_id', $user->id);
                })->exists();
            if ($isMappedActivity) {
                return true;
            }
        }

        return false;
    }
}
