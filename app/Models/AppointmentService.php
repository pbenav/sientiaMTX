<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentService extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
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
        'translations',
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
        'translations'  => 'array',
    ];

    /**
     * Accesor para obtener el nombre traducido al idioma actual, o el original si no existe.
     */
    public function getNameAttribute($value)
    {
        $locale = app()->getLocale();
        if ($locale !== 'es' && is_array($this->translations) && isset($this->translations[$locale]['name'])) {
            return $this->translations[$locale]['name'];
        }
        return $value;
    }

    /**
     * Accesor para obtener la descripción traducida, o la original si no existe.
     */
    public function getDescriptionAttribute($value)
    {
        $locale = app()->getLocale();
        if ($locale !== 'es' && is_array($this->translations) && isset($this->translations[$locale]['description'])) {
            return $this->translations[$locale]['description'];
        }
        return $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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

    protected static function booted()
    {
        static::saved(function ($service) {
            // Si cambian los textos o es nuevo
            if ($service->wasChanged('name') || $service->wasChanged('description') || $service->wasRecentlyCreated) {
                // Despachamos el job
                \App\Jobs\TranslateAppointmentServiceJob::dispatch($service);
            }
        });
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
