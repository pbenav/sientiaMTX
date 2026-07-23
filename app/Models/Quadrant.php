<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cuadrante con colores y orden de visualización.
 *
 * Representa un cuadrante (ej: matriz de Eisenhower) con nombre,
 * slug, descripción, color hexadecimal y orden de visualización.
 *
 * Campos clave:
 * - name: Nombre del cuadrante
 * - slug: Slug único del cuadrante
 * - description: Descripción del cuadrante
 * - color_hex: Color hexadecimal del cuadrante
 * - color_description: Descripción descriptiva del color
 * - order: Orden de visualización
 *
 * @property-read string $name
 * @property-read string $slug
 * @property-read string|null $description
 * @property-read string|null $color_hex
 * @property-read string|null $color_description
 * @property-read int|null $order
 *
 * @mixin Builder
 */
class Quadrant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'color_hex', 'color_description', 'order'];
}
