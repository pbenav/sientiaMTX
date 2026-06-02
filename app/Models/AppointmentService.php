<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentService extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'modality',
        'description',
        'duration_minutes',
        'slot_duration_minutes',
        'max_per_slot',
        'price',
        'price_visible',
        'is_active',
        'sort_order',
    ];

    const MODALITIES = [
        'presencial' => 'Presencial',
        'jitsi'      => 'Videoconferencia (Jitsi)',
        'meet'       => 'Videoconferencia (Google Meet)',
    ];

    protected $casts = [
        'modality'      => 'array',
        'price'         => 'decimal:2',
        'price_visible' => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AppointmentSchedule::class, 'service_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(AppointmentBlock::class, 'service_id');
    }

    /**
     * Duración efectiva: propia o heredada del setting del usuario.
     */
    public function getEffectiveSlotDuration(): int
    {
        if ($this->slot_duration_minutes) {
            return $this->slot_duration_minutes;
        }
        return $this->user->appointmentSettings?->default_slot_duration ?? 15;
    }

    /**
     * Máximo efectivo por tramo: propio o heredado del setting del usuario.
     */
    public function getEffectiveMaxPerSlot(): int
    {
        if ($this->max_per_slot) {
            return $this->max_per_slot;
        }
        return $this->user->appointmentSettings?->default_max_per_slot ?? 1;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
