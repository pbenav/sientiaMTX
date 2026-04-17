<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAiPreference extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'default_provider',
        'ai_model',
        'api_key',
        'smart_matching_opt_in',
        'mood_tracking_enabled',
        'ai_settings',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    protected $casts = [
        'smart_matching_opt_in' => 'boolean',
        'mood_tracking_enabled' => 'boolean',
        'ai_settings' => 'array',
        'api_key' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
