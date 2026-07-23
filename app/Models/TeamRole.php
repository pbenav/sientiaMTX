<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rol de equipo con descripción opcional.
 *
 * Define un rol dentro de un equipo, con nombre y descripción,
 * utilizado para asignar permisos y responsabilidades.
 *
 * Campos clave:
 * - name: Nombre del rol (ej: "moderator", "member", "admin")
 * - description: Descripción del rol
 *
 * @property-read string $name
 * @property-read string|null $description
 *
 * @mixin Builder
 */
class TeamRole extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * Scope: excluye el rol de moderator.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcludeModerator($query)
    {
        return $query->where('name', '!=', 'moderator');
    }
}
