<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Survey extends Model
{
    use HasFactory;

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
        'type',
        'is_active',
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
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
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

    public function options(): HasMany
    {
        return $this->hasMany(SurveyOption::class)->orderBy('order');
    }

    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'survey_votes')
            ->withPivot('option_id', 'voted_at')
            ->withTimestamps();
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SurveyVote::class);
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

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
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

    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getOptionsCountAttribute(): int
    {
        return $this->options()->count();
    }

    /**
     * Check if the given user has voted in this survey.
     */
    public function hasVoted(User $user): bool
    {
        return $this->voters()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the vote count for a specific option.
     */
    public function getOptionVoteCount(int $optionId): int
    {
        return $this->votes()->where('option_id', $optionId)->count();
    }

    /**
     * Get the percentage of votes for a specific option.
     */
    public function getOptionPercentage(int $optionId): float
    {
        $totalVotes = $this->vote_count;
        if ($totalVotes === 0) {
            return 0.0;
        }
        $optionVotes = $this->getOptionVoteCount($optionId);
        return round(($optionVotes / $totalVotes) * 100, 1);
    }
}
