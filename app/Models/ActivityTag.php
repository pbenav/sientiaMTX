<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTag extends Model
{
    protected $fillable = ['activity_id', 'tag', 'color_hex'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
