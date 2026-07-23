<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registro de estado de ánimo y energía del usuario.
 *
 * Almacena registros periódicos del estado de ánimo y nivel de
 * energía del usuario, vinculados opcionalmente a una tarea específica.
 *
 * Campos clave:
 * - user_id: ID del usuario
 * - task_id: ID de la tarea asociada (si aplica)
 * - energy_level: Nivel de energía (escala numérica)
 * - mood_label: Etiqueta descriptiva del estado de ánimo
 * - notes: Notas adicionales sobre el estado del usuario
 *
 * @property-read int $user_id
 * @property-read int|null $task_id
 * @property-read int|null $energy_level
 * @property-read string|null $mood_label
 * @property-read string|null $notes
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Task|null $task
 *
 * @mixin Builder
 */
class UserMoodLog extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'energy_level',
        'mood_label',
        'notes',
    ];

    /**
     * Relación de pertenencia al usuario del registro.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia a la tarea asociada al registro.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
