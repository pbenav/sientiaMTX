<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Nota privada o pública de actividad con visibilidad controlada.
 *
 * Permite adjuntar notas a una actividad, marcándolas como privadas
 * (solo visibles por el creador) o públicas (visibles por todo el
 * equipo con acceso a la actividad).
 *
 * Campos clave:
 * - activity_id: ID de la actividad a la que pertenece la nota
 * - user_id: ID del usuario que creó la nota
 * - content: Contenido de la nota
 * - visibility: 'private', 'public' o null (null se trata como private)
 *
 * @property-read int $activity_id
 * @property-read int $user_id
 * @property-read string $content
 * @property-read string|null $visibility
 *
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class ActivityNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['activity_id', 'user_id', 'content', 'visibility'];

    /**
     * Relación de pertenencia a la actividad asociada.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Relación de pertenencia al usuario que creó la nota.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica si la nota es privada.
     *
     * Una nota es privada si su visibilidad es 'private' o null.
     *
     * @return bool True si la nota es privada
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private' || is_null($this->visibility);
    }
}
