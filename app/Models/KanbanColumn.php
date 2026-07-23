<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Columna de Kanban para tareas y actividades.
 *
 * Define una columna en un tablero Kanban de equipo, con orden
 * de aparición, tipo, progreso por defecto y color visual.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece la columna
 * - title: Título de la columna (ej: "Por hacer", "En progreso", "Hecho")
 * - order_index: Orden de aparición en el tablero
 * - type: Tipo de la columna (tasks, activities, etc.)
 * - default_progress: Progreso por defecto para elementos en esta columna
 * - color: Color visual de la columna
 *
 * @property-read int $team_id
 * @property-read string $title
 * @property-read int $order_index
 * @property-read string|null $type
 * @property-read int|null $default_progress
 * @property-read string|null $color
 *
 * @property-read \App\Models\Team $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
 *
 * @mixin Builder
 */
class KanbanColumn extends Model
{
    protected $fillable = [
        'team_id',
        'title',
        'order_index',
        'type',
        'default_progress',
        'color',
    ];

    /**
     * Relación de pertenencia al equipo de la columna.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación uno-a-muchos con las tareas en esta columna.
     *
     * @return HasMany<\App\Models\Task, $this>
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Relación uno-a-muchos con las actividades en esta columna.
     *
     * @return HasMany<\App\Models\Activity, $this>
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'kanban_column_id');
    }
}
