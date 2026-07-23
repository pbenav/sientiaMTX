<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Nota rápida con posicionamiento en interfaz.
 *
 * Representa una nota rápida personal con posición, tamaño,
 * color y estado visual, utilizada en dashboards y paneles.
 *
 * Campos clave:
 * - user_id: ID del usuario propietario de la nota
 * - content: Contenido de la nota
 * - position_x: Posición horizontal en la interfaz
 * - position_y: Posición vertical en la interfaz
 * - width: Ancho de la nota en píxeles
 * - height: Alto de la nota en píxeles
 * - color: Color de fondo de la nota
 * - is_pinned: Si la nota está fijada
 * - is_minimized: Si la nota está minimizada
 * - is_hidden: Si la nota está oculta
 * - attachments: Archivos adjuntos en formato array
 *
 * @property-read int $user_id
 * @property-read string $content
 * @property-read int $position_x
 * @property-read int $position_y
 * @property-read int $width
 * @property-read int $height
 * @property-read string|null $color
 * @property-read bool $is_pinned
 * @property-read bool $is_minimized
 * @property-read bool $is_hidden
 * @property-read array|null $attachments
 *
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class QuickNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'position_x',
        'position_y',
        'width',
        'height',
        'color',
        'is_pinned',
        'is_minimized',
        'is_hidden',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'is_minimized' => 'boolean',
        'is_hidden' => 'boolean',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Relación de pertenencia al usuario propietario de la nota.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
