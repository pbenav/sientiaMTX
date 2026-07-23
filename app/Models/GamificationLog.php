<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registro de puntos de gamificación.
 *
 * Registra cada ganancia de puntos para un usuario dentro de un equipo,
 * con información sobre el tipo de acción, la fuente (modelo e ID)
 * y una descripción opcional.
 *
 * Campos clave:
 * - user_id: ID del usuario que recibió los puntos
 * - team_id: ID del equipo al que pertenecen los puntos
 * - points: Cantidad de puntos otorgados (puede ser negativo)
 * - type: Tipo de acción (ej: "task_completed", "login", "streak")
 * - source_type: Tipo de modelo fuente (ej: "App\Models\Task")
 * - source_id: ID del modelo fuente
 * - description: Descripción opcional del evento
 *
 * @property-read int $user_id
 * @property-read int $team_id
 * @property-read int $points
 * @property-read string $type
 * @property-read string $source_type
 * @property-read int $source_id
 * @property-read string|null $description
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class GamificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'points',
        'type',
        'source_type',
        'source_id',
        'description',
    ];

    /**
     * Relación de pertenencia al usuario que recibió los puntos.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo asociado.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
