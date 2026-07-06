<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAssignment extends Model
{
    protected $fillable = [
        'activity_id', 'user_id', 'group_id', 'assigned_by_id', 'assigned_at', 'completed_at',
    ];

    protected static function booted()
    {
        static::creating(function ($assignment) {
            if (empty($assignment->assigned_at)) {
                $assignment->assigned_at = now();
            }
        });
    }

    protected $casts = [
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
