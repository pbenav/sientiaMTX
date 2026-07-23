<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Etiqueta de actividad con color hexadecimal.
 *
 * Permite asignar etiquetas con colores personalizados a las
 * actividades para facilitar su organización visual.
 *
 * Campos clave:
 * - activity_id: ID de la actividad etiquetada
 * - tag: Texto de la etiqueta
 * - color_hex: Código hexadecimal del color de la etiqueta
 *
 * @property-read int $activity_id
 * @property-read string $tag
 * @property-read string $color_hex
 *
 * @property-read \App\Models\Activity $activity
 *
 * @mixin Builder
 */
class ActivityTag extends Model
{
    protected $fillable = ['activity_id', 'tag', 'color_hex'];

    /**
     * Relación de pertenencia a la actividad etiquetada.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
