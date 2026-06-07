<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use App\Traits\HasDemoMasking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasDemoMasking;

    protected array $demoSensitiveAttributes = [
        'author_name'     => 'name',
        'text'            => 'text',
        'reply_to_text'   => 'text',
    ];
    protected $fillable = [
        'team_id',
        'user_id',
        'author_name',
        'text',
        'photo_path',
        'telegram_message_id',
        'is_from_web',
        'is_deleted_on_telegram',
        'voice_path',
        'voice_duration',
        'sticker_path',
        'file_type',
        'file_size',
        'reply_to_message_id',
        'reply_to_text',
    ];

    protected static function booted()
    {
        // Lifecycle events are now handled by App\Observers\TelegramMessageObserver
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public URL for the photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) return null;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->photo_path)) return null;
        return asset('storage/' . $this->photo_path);
    }

    public function getVoiceUrlAttribute(): ?string
    {
        if (!$this->voice_path) return null;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->voice_path)) return null;
        return asset('storage/' . $this->voice_path);
    }

    public function getStickerUrlAttribute(): ?string
    {
        if (!$this->sticker_path) return null;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->sticker_path)) return null;
        return asset('storage/' . $this->sticker_path);
    }
}
