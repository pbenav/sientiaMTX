<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
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
    ];

    protected static function booted()
    {
        static::created(function ($message) {
            if ($message->file_size > 0 && $message->team) {
                $message->team->increment('disk_used', $message->file_size);
                // Refresh usage and check for alerts
                $message->team->refresh()->checkStorageAlerts();
            }
        });

        static::deleted(function ($message) {
            if ($message->file_size > 0 && $message->team) {
                $message->team->decrement('disk_used', max(0, $message->file_size));
            }
        });
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
