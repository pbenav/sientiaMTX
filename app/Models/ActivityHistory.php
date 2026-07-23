<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Historial de cambios de actividad.
 *
 * Registra cada modificación realizada a una actividad, incluyendo
 * los valores anteriores y nuevos en formato JSON, el usuario que
 * realizó el cambio, la acción ejecutada y notas opcionales.
 *
 * Campos clave:
 * - activity_id: ID de la actividad modificada
 * - user_id: ID del usuario que realizó el cambio
 * - action: Descripción de la acción (ej: "status_changed", "title_updated")
 * - old_values: Valores anteriores en formato array/JSON
 * - new_values: Valores nuevos en formato array/JSON
 * - notes: Notas opcionales sobre el cambio
 *
 * @property-read int $activity_id
 * @property-read int $user_id
 * @property-read string $action
 * @property-read array $old_values
 * @property-read array $new_values
 * @property-read string|null $notes
 *
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class ActivityHistory extends Model
{

    protected $fillable = [
        'activity_id', 'user_id', 'action', 'old_values', 'new_values', 'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Relación de pertenencia a la actividad modificada.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Relación de pertenencia al usuario que realizó el cambio.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
