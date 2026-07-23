<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registro de seguimiento de tiempo.
 *
 * Almacena registros de tiempo dedicados a tareas/actividades, con
 * hora de inicio, fin, tipo y nota descriptiva.
 *
 * Campos clave:
 * - user_id: ID del usuario que registra el tiempo
 * - task_id: ID de la tarea/actividad asociada
 * - type: Tipo de registro (ej: "work", "break", "meeting")
 * - start_at: Fecha/hora de inicio
 * - end_at: Fecha/hora de fin
 * - note: Nota descriptiva del registro
 *
 * @property-read int $user_id
 * @property-read int $task_id
 * @property-read string $type
 * @property-read \Carbon\Carbon $start_at
 * @property-read \Carbon\Carbon $end_at
 * @property-read string|null $note
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Activity $activity
 *
 * @mixin Builder
 */
class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'type',
        'start_at',
        'end_at',
        'note',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia al usuario que registra el tiempo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia a la actividad asociada al registro de tiempo.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'task_id');
    }

    /**
     * Relación de pertenencia a la tarea asociada (obsoleta).
     *
     * @deprecated Use activity() instead
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Activity, $this>
     */
    public function task()
    {
        return $this->activity();
    }
}
