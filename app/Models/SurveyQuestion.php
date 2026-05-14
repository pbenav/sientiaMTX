<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'title',
        'description',
        'instructions',
        'type',
        'order',
        'is_required',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
    ];

    // Relationships

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(SurveyOption::class, 'question_id')->orderBy('order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SurveyVote::class, 'question_id');
    }

    // Helpers

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'single_choice' => __('Opción Única'),
            'multiple_choice' => __('Opción Múltiple'),
            'rating' => __('Valoración'),
            'text' => __('Texto Libre'),
            default => $this->type
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'single_choice' => 'from-purple-500 to-indigo-600',
            'multiple_choice' => 'from-blue-500 to-cyan-600',
            'rating' => 'from-amber-400 to-orange-500',
            'text' => 'from-emerald-500 to-teal-600',
            default => 'from-gray-500 to-slate-600'
        };
    }
}
