<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Asignación de actividad a usuarios, grupos o ambos.
 *
 * Registra quién o qué grupo es responsable de ejecutar una actividad,
 * junto con la fecha de asignación y la fecha de completado.
 *
 * Campos clave:
 * - activity_id: ID de la actividad asignada
 * - user_id: ID del usuario asignado (si aplica)
 * - group_id: ID del grupo asignado (si aplica)
 * - assigned_by_id: ID del usuario que creó la asignación
 * - assigned_at: Fecha/hora de asignación
 * - completed_at: Fecha/hora de completado (si ya se ejecutó)
 *
 * @property-read int $activity_id
 * @property-read int|null $user_id
 * @property-read int|null $group_id
 * @property-read int $assigned_by_id
 * @property-read \Carbon\Carbon|null $assigned_at
 * @property-read \Carbon\Carbon|null $completed_at
 *
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Group $group
 * @property-read \App\Models\User $assignedBy
 *
 * @mixin Builder
 */
class ActivityAssignment extends Model
{
    protected $fillable = [
        'activity_id', 'user_id', 'group_id', 'assigned_by_id', 'assigned_at', 'completed_at',
    ];

    protected static function booted()
    {
        static::creating(function ($assignment) {
            if (empty($assignment->assigned_at)) {
                $assignment->assigned_at = now();
            }
        });
    }

    protected $casts = [
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia a la actividad asignada.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Relación de pertenencia al usuario asignado.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al grupo asignado.
     *
     * @return BelongsTo<\App\Models\Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Relación de pertenencia al usuario que realizó la asignación.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
