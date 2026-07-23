<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calificación de actividad con puntuación y comentario opcional.
 *
 * Permite a los usuarios puntuar actividades con una calificación
 * numérica y un comentario opcional, clasificando el tipo de
 * calificación.
 *
 * Campos clave:
 * - activity_id: ID de la actividad calificada
 * - user_id: ID del usuario que calificó
 * - score: Puntuación numérica otorgada
 * - type: Tipo de calificación (ej: "quality", "effort", "satisfaction")
 * - comment: Comentario opcional sobre la calificación
 *
 * @property-read int $activity_id
 * @property-read int $user_id
 * @property-read int|float $score
 * @property-read string $type
 * @property-read string|null $comment
 *
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class ActivityRating extends Model
{
    protected $fillable = ['activity_id', 'user_id', 'score', 'type', 'comment'];

    /**
     * Relación de pertenencia a la actividad calificada.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Relación de pertenencia al usuario que realizó la calificación.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
