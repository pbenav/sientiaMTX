<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agradecimiento (kudo) entre miembros del equipo.
 *
 * Permite a los usuarios enviar reconocimientos públicos a otros
 * miembros del equipo, vinculados opcionalmente a una tarea.
 *
 * Campos clave:
 * - from_user_id: ID del usuario que envía el kudo
 * - to_user_id: ID del usuario que recibe el kudo
 * - team_id: ID del equipo al que pertenecen
 * - task_id: ID de la tarea asociada (si aplica)
 * - type: Tipo de kudo (ej: "appreciation", "kudos", "thanks")
 * - message: Mensaje opcional del kudo
 *
 * @property-read int $from_user_id
 * @property-read int $to_user_id
 * @property-read int $team_id
 * @property-read int|null $task_id
 * @property-read string $type
 * @property-read string|null $message
 *
 * @property-read \App\Models\User $sender
 * @property-read \App\Models\User $receiver
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\Task|null $task
 *
 * @mixin Builder
 */
class Kudo extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'team_id',
        'task_id',
        'type',
        'message',
    ];

    /**
     * Relación de pertenencia al usuario que envía el kudo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Relación de pertenencia al usuario que recibe el kudo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Relación de pertenencia al equipo del kudo.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia a la tarea asociada al kudo.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
