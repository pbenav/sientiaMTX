<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Nota privada de tarea.
 *
 * Representa una nota privada asociada a una tarea, escrita por
 * un usuario, visible solo para usuarios con permisos.
 *
 * Campos clave:
 * - task_id: ID de la tarea asociada
 * - user_id: ID del usuario que escribió la nota
 * - content: Contenido de la nota
 *
 * @property-read int $task_id
 * @property-read int $user_id
 * @property-read string $content
 *
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class TaskPrivateNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    /**
     * Relación de pertenencia a la tarea de la nota.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relación de pertenencia al usuario que escribió la nota.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
