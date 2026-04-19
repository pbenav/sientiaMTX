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
        // 'api_key' => 'encrypted', // Quitamos el cast automático que rompe la app
    ];

    /**
     * Accesor manual con seguridad ante fallos de desencriptación (The MAC is invalid)
     */
    public function getApiKeyAttribute($value)
    {
        if (empty($value)) return null;

        try {
            return decrypt($value);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Error desencriptando API Key para preferencia {$this->id}: " . $e->getMessage());
            return null; // Devolvemos null para que el usuario pueda re-introducirla
        }
    }

    /**
     * Mutador manual para asegurar que se guarde encriptada
     */
    public function setApiKeyAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['api_key'] = null;
        } else {
            $this->attributes['api_key'] = encrypt($value);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
