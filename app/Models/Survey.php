<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use App\Traits\HasUuid;

/**
 * Encuesta / Survey: herramienta de votación con preguntas y opciones.
 *
 * Características:
 * - UUID para URLs públicas
 * - Control de expiración y cierre
 * - Votación pública o restringida al equipo
 * - Opción de mostrar resultados antes de votar
 * - Restricción de múltiples votos por usuario
 */
class Survey extends Model
{
    use HasFactory, HasUuid;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'created_by_id',
        'title',
        'description',
        'is_active',
        'is_public',
        'allow_multiple_votes',
        'show_results_before_voting',
        'expires_at',
        'published_at',
        'closed_at',
        'uuid',
        'data_protection',
    ];

    /**
     * Casting de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'data_protection' => 'array',
        ];
    }

    // Relationships

    /**
     * Equipo propietario de la encuesta.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Usuario creador de la encuesta.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Preguntas de la encuesta, ordenadas por orden.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    /**
     * Opciones de las preguntas de la encuesta (relación a través).
     */
    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyOption::class, SurveyQuestion::class, 'survey_id', 'question_id');
    }

    /**
     * Votos registrados en la encuesta (relación a través).
     */
    public function votes(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyVote::class, SurveyQuestion::class, 'survey_id', 'question_id');
    }

    // Scopes

    /**
     * Scope: encuestas activas (is_active=true y no expiradas).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // Accessors

    /**
     * Verifica si la encuesta ha expirado (expires_at en el pasado).
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Verifica si la encuesta está cerrada (closed_at o expirada).
     */
    public function getIsClosedAttribute(): bool
    {
        return $this->closed_at !== null || $this->isExpired;
    }

    /**
     * Verifica si un usuario específico ya votó en esta encuesta.
     *
     * @param  User  $user  Usuario a verificar
     * @return bool true si el usuario ya emitió su voto
     */
    public function hasVoted(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }
}
