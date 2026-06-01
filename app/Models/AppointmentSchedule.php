<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'max_per_slot',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const DAYS = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '?';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
