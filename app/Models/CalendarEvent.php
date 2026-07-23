<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Evento de calendario con sincronización con Google Calendar.
 *
 * Representa un evento en el calendario del equipo, vinculado a una
 * tarea opcionalmente, con soporte para sincronización con Google
 * Calendar mediante google_calendar_id.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece el evento
 * - task_id: ID de la tarea asociada (si aplica)
 * - title: Título del evento
 * - description: Descripción del evento
 * - start_time: Fecha y hora de inicio
 * - end_time: Fecha y hora de fin
 * - all_day: Si es evento de día completo
 * - location: Ubicación del evento
 * - google_calendar_id: ID del evento en Google Calendar
 *
 * @property-read int $team_id
 * @property-read int|null $task_id
 * @property-read string $title
 * @property-read string|null $description
 * @property-read \Carbon\Carbon $start_time
 * @property-read \Carbon\Carbon $end_time
 * @property-read bool $all_day
 * @property-read string|null $location
 * @property-read string|null $google_calendar_id
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\Task|null $task
 *
 * @mixin Builder
 */
class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'task_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'all_day',
        'location',
        'google_calendar_id'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'all_day' => 'boolean'
    ];

    /**
     * Relación de pertenencia al equipo del evento.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia a la tarea asociada al evento.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
