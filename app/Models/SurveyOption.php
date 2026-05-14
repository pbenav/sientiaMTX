<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyOption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'survey_id',
        'label',
        'description',
        'order',
        'color',
        'is_other',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_other' => 'boolean',
        ];
    }

    // Relationships

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SurveyVote::class);
    }

    // Accessors

    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }
}
