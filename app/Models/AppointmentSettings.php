<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentSettings extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'public_slug',
        'display_name',
        'is_public',
        'welcome_text',
        'legal_text',
        'default_slot_duration',
        'default_max_per_slot',
        'google_calendar_enabled',
        'default_expediente_id',
        'auto_create_task',
        'email_confirmation',
        'jitsi_domain',
    ];

    protected $casts = [
        'is_public'                => 'boolean',
        'google_calendar_enabled'  => 'boolean',
        'auto_create_task'         => 'boolean',
        'email_confirmation'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function defaultExpediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'default_expediente_id');
    }
}
