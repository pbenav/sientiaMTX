<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Publicación oficial en Tablón Edictal / Portal de la Ciudadanía.
 *
 * Expone un documento o expediente que ha completado satisfactoriamente
 * su circuito de tramitación y aprobación.
 *
 * @property int $id
 * @property int $team_id
 * @property int $circuit_execution_id
 * @property int $published_by_id
 * @property string $title
 * @property string $document_code
 * @property string|null $summary
 * @property string $channel ('internal_board', 'microsite', 'external_api')
 * @property string $status ('draft', 'published', 'expired', 'withdrawn')
 * @property string $public_slug
 * @property string|null $public_token
 * @property \Carbon\Carbon $publication_date
 * @property \Carbon\Carbon|null $expiration_date
 * @property string|null $content_html
 * @property array|null $attachments
 * @property string|null $external_reference_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\CircuitExecution $execution
 * @property-read \App\Models\User $publisher
 */
class CircuitPublication extends Model
{
    use HasFactory, SoftDeletes;

    public const CHANNEL_INTERNAL = 'internal_board';
    public const CHANNEL_MICROSITE = 'microsite';
    public const CHANNEL_EXTERNAL_API = 'external_api';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'team_id',
        'circuit_execution_id',
        'published_by_id',
        'title',
        'document_code',
        'summary',
        'channel',
        'status',
        'public_slug',
        'public_token',
        'publication_date',
        'expiration_date',
        'content_html',
        'attachments',
        'external_reference_id',
        'metadata',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'expiration_date' => 'date',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CircuitPublication $pub) {
            if (empty($pub->public_slug)) {
                $base = Str::slug($pub->title) ?: 'edicto';
                $pub->public_slug = $base . '-' . Str::random(8);
            }
            if (empty($pub->public_token)) {
                $pub->public_token = Str::random(32);
            }
        });
    }

    /**
     * Equipo emisor de la publicación.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Tramitación origen de la que emana esta publicación.
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(CircuitExecution::class, 'circuit_execution_id');
    }

    /**
     * Usuario que ordenó o firmó la publicación.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_id');
    }

    /**
     * Scope para publicaciones activas vigentes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('publication_date', '<=', now()->toDateString())
            ->where(function (Builder $q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now()->toDateString());
            });
    }

    /**
     * Comprueba si la publicación está vigente en este momento.
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        $today = now()->startOfDay();
        if ($this->publication_date && $this->publication_date->startOfDay()->isAfter($today)) {
            return false;
        }

        if ($this->expiration_date && $this->expiration_date->startOfDay()->isBefore($today)) {
            return false;
        }

        return true;
    }
}

