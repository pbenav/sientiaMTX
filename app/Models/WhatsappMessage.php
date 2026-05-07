<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'message_id',
        'from_me',
        'author',
        'text',
        'file_type',
        'file_mime_type',
        'photo_path',
        'voice_path',
        'sticker_path',
        'animation_path',
        'file_size',
        'reply_to_id',
        'reply_to_text',
        'is_deleted'
    ];

    protected $casts = [
        'from_me' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
