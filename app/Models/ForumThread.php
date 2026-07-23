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
     * Relación uno-a-muchos con los mensajes del hilo.
     *
     * @return HasMany<\App\Models\ForumMessage, $this>
     */
    public function messages()
    {
        return $this->hasMany(ForumMessage::class);
    }

    /**
     * Scope: hilos huérfanos (sin tarea vinculada) = Biblioteca de Conocimiento.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrphaned($query)
    {
        return $query->whereNull('task_id');
    }

    /**
     * Determina si este hilo es parte de la "Biblioteca de Conocimiento".
     *
     * @return bool True si el hilo no tiene tarea vinculada
     */
    public function isKnowledgeLibrary(): bool
    {
        return is_null($this->task_id);
    }
}
