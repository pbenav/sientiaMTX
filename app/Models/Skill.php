<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\GeneratesSlug;

/**
 * Habilidad con asignación a usuarios y actividades.
 *
 * Representa una habilidad del sistema, vinculada a un equipo, con
 * asignación a usuarios (con nivel y XP) y asociación a actividades.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece (null si es global)
 * - name: Nombre de la habilidad
 * - slug: Slug único de la habilidad
 * - description: Descripción de la habilidad
 * - color: Color asociado a la habilidad
 * - icon: Icono asociado a la habilidad
 *
 * @property-read int|null $team_id
 * @property-read string $name
 * @property-read string $slug
 * @property-read string|null $description
 * @property-read string|null $color
 * @property-read string|null $icon
 *
 * @property-read \App\Models\Team|null $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $tasks
 *
 * @mixin Builder
 */
class Skill extends Model
{
    use HasFactory, GeneratesSlug;

    protected $fillable = ['team_id', 'name', 'slug', 'description', 'color', 'icon'];

    /**
     * Relación de pertenencia al equipo de la habilidad.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación muchos-a-muchos con usuarios que tienen esta habilidad.
     *
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills')->withPivot('level', 'total_xp')->withTimestamps();
    }

    /**
     * Relación muchos-a-muchos con actividades (tareas) que requieren esta habilidad.
     *
     * @return BelongsToMany<\App\Models\Activity, $this>
     */
    public function tasks()
    {
        return $this->belongsToMany(Activity::class, 'activity_skills', 'skill_id', 'activity_id')
                    ->where('type', 'task');
    }

    /**
     * Scope: habilidades para un equipo o globales.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $teamId ID del equipo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTeamOrGlobal($query, $teamId)
    {
        return $query->where(function($q) use ($teamId) {
            $q->where('team_id', $teamId)
              ->orWhere(function($subQ) use ($teamId) {
                  $subQ->whereNull('team_id')
                       ->whereNotIn('name', function($nameQuery) use ($teamId) {
                           $nameQuery->select('name')
                                     ->from('skills')
                                     ->where('team_id', $teamId);
                       });
              });
        });
    }
}
