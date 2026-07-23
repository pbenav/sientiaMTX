<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Invitación a equipo con rol asignado.
 *
 * Representa una invitación enviada a un usuario para unirse a un
 * equipo, con un rol específico y token de verificación.
 *
 * Campos clave:
 * - email: Email del usuario invitado
 * - team_id: ID del equipo al que se invita
 * - role_id: ID del rol asignado
 * - token: Token de verificación de la invitación
 *
 * @property-read string $email
 * @property-read int $team_id
 * @property-read int|null $role_id
 * @property-read string $token
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\TeamRole|null $role
 *
 * @mixin Builder
 */
class TeamInvitation extends Model
{
    protected $fillable = ['email', 'team_id', 'role_id', 'token'];

    /**
     * Relación de pertenencia al equipo invitado.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia al rol asignado en la invitación.
     *
     * @return BelongsTo<\App\Models\TeamRole, $this>
     */
    public function role()
    {
        return $this->belongsTo(TeamRole::class, 'role_id');
    }
}
