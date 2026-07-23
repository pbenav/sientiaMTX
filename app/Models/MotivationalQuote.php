<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cita motivacional con autor y tipo.
 *
 * Almacena citas motivacionales utilizadas en el sistema para
 * inspirar y motivar a los miembros del equipo.
 *
 * Campos clave:
 * - text: Texto de la cita motivacional
 * - author: Autor de la cita
 * - type: Tipo de cita (ej: "productivity", "wellness", "teamwork")
 *
 * @property-read string $text
 * @property-read string|null $author
 * @property-read string|null $type
 *
 * @mixin Builder
 */
class MotivationalQuote extends Model
{
    protected $fillable = ['text', 'author', 'type'];
}
