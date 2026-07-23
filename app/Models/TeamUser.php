<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pivot entre usuario y equipo con tokens de Google.
 *
 * Representa la relación muchos-a-muchos entre usuarios y equipos,
 * con datos adicionales como token de Google, fecha de unión y
 * otros metadatos de la relación.
 *
 * Campos clave:
 * - team_id: ID del equipo
 * - user_id: ID del usuario
 * - google_token: Token de Google en formato array
 * - joined_at: Fecha/hora de unión al equipo
 *
 * @property-read int $team_id
 * @property-read int $user_id
 * @property-read array|null $google_token
 * @property-read \Carbon\Carbon|null $joined_at
 *
 * @mixin Builder
 */
class TeamUser extends Pivot
{
    /**
     * The table associated with the pivot model.
     *
     * @var string
     */
    protected $table = 'team_user';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'google_token' => 'array',
        'joined_at' => 'datetime',
    ];
}
