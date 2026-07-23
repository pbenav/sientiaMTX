<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Asignación de tarea a usuario o grupo.
 *
 * Representa la asignación de una tarea a un usuario o grupo, con
 * fecha de asignación y usuario que realizó la asignación.
 *
 * Campos clave:
 * - task_id: ID de la tarea asignada
 * - user_id: ID del usuario asignado
 * - group_id: ID del grupo asignado (si aplica)
 * - assigned_at: Fecha/hora de asignación
 * - assigned_by_id: ID del usuario que realizó la asignación
 *
 * @property-read int $task_id
 * @property-read int $user_id
 * @property-read int|null $group_id
 * @property-read \Carbon\Carbon $assigned_at
 * @property-read int|null $assigned_by_id
 *
 * @property-read \App\Models\Group $group
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User $assignedBy
 *
 * @mixin Builder
 */
class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'user_id', 'group_id', 'assigned_at', 'assigned_by_id'];

    /**
     * Relación de pertenencia al grupo de la asignación.
     *
     * @return BelongsTo<\App\Models\Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    /**
     * Relación de pertenencia a la tarea asignada.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relación de pertenencia al usuario asignado.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al usuario que realizó la asignación.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
