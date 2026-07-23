<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Grupo de tareas con asignaciones y usuarios.
 *
 * Representa un grupo de tareas dentro de un equipo, con usuarios
 * asociados y asignaciones de tareas.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece el grupo
 * - name: Nombre del grupo
 * - description: Descripción del grupo
 *
 * @property-read int $team_id
 * @property-read string $name
 * @property-read string|null $description
 *
 * @property-read \App\Models\Team $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskAssignment> $assignments
 *
 * @mixin Builder
 */
class Group extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'name', 'description'];

    /**
     * Relación de pertenencia al equipo del grupo.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación muchos-a-muchos con usuarios del grupo.
     *
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Relación uno-a-muchos con asignaciones de tareas del grupo.
     *
     * @return HasMany<\App\Models\TaskAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }
}
