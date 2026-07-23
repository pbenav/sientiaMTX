<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Historial de cambios de tarea.
 *
 * Almacena el historial de acciones realizadas sobre una tarea,
 * con valores antiguos, nuevos, notas y usuario responsable.
 *
 * Campos clave:
 * - task_id: ID de la tarea modificada
 * - user_id: ID del usuario que realizó el cambio
 * - action: Tipo de acción realizada
 * - old_values: Valores anteriores en formato array
 * - new_values: Valores nuevos en formato array
 * - notes: Notas adicionales sobre el cambio
 *
 * @property-read int $task_id
 * @property-read int $user_id
 * @property-read string $action
 * @property-read array|null $old_values
 * @property-read array|null $new_values
 * @property-read string|null $notes
 *
 * @property-read string $action_label
 *
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class TaskHistory extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'user_id', 'action', 'old_values', 'new_values', 'notes'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];

    /**
     * Relación de pertenencia a la tarea del historial.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relación de pertenencia al usuario que realizó el cambio.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Atributo accesible: etiqueta de la acción en mayúsculas.
     *
     * @return string
     */
    public function getActionLabelAttribute(): string
    {
        return strtoupper($this->action ?? 'UPDATED');
    }
}
