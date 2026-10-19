<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Plantilla de circuito de tramitación / aprobación.
 *
 * Define el flujo estandarizado de pasos que una actividad o expediente
 * debe recorrer (visto bueno, justificación, aprobación, publicación).
 *
 * @property int $id
 * @property int $team_id
 * @property int $created_by_id
 * @property string $name
 * @property string|null $description
 * @property string $target_type ('activity', 'expediente', 'both')
 * @property bool $is_active
 * @property array|null $settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CircuitTemplateStep> $steps
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CircuitExecution> $executions
 */
class CircuitTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'created_by_id',
        'name',
        'description',
        'target_type',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Equipo al que pertenece la plantilla.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Usuario que creó la plantilla.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Pasos ordenados que componen esta plantilla.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(CircuitTemplateStep::class)->orderBy('order', 'asc');
    }

    /**
     * Ejecuciones instanciadas a partir de esta plantilla.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(CircuitExecution::class);
    }

    /**
     * Scope para plantillas activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tipo de destino (activity, expediente o both).
     */
    public function scopeForTarget(Builder $query, string $targetType): Builder
    {
        return $query->whereIn('target_type', [$targetType, 'both']);
    }
}

