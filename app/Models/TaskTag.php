<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Etiqueta de tarea con color personalizado.
 *
 * Representa una etiqueta asociada a una tarea, con texto y color
 * hexadecimal personalizado.
 *
 * Campos clave:
 * - task_id: ID de la tarea asociada
 * - tag: Texto de la etiqueta
 * - color_hex: Color hexadecimal de la etiqueta
 *
 * @property-read int $task_id
 * @property-read string $tag
 * @property-read string|null $color_hex
 *
 * @property-read \App\Models\Task $task
 *
 * @mixin Builder
 */
class TaskTag extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'tag', 'color_hex'];

    /**
     * Relación de pertenencia a la tarea de la etiqueta.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
