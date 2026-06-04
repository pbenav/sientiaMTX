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

class Survey extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
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
    ];

    /**
     * The attributes that should be cast.
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
        ];
    }

    // Relationships

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyOption::class, SurveyQuestion::class, 'survey_id', 'question_id');
    }

    public function votes(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyVote::class, SurveyQuestion::class, 'survey_id', 'question_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // Accessors

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->closed_at !== null || $this->isExpired;
    }

    /**
     * Check if the given user has voted in this survey.
     */
    public function hasVoted(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }
}
