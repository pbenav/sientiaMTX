<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramGroupMember extends Model
{
    protected $fillable = [
        'team_id',
        'telegram_user_id',
        'username',
        'first_name',
        'last_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
