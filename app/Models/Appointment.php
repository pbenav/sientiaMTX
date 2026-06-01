<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    protected $fillable = [
        'localizador',
        'user_id',
        'service_id',
        'visitor_id',
        'appointment_date',
        'appointment_time',
        'slot_duration_minutes',
        'status',
        'member_notes',
        'task_id',
        'expediente_id',
        'google_event_id',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'cancelled_at'     => 'datetime',
    ];

    const STATUSES = [
        'pending'   => 'Pendiente',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada',
        'completed' => 'Completada',
        'blocked'   => 'Bloqueada',
    ];

    const STATUS_COLORS = [
        'pending'   => 'yellow',
        'confirmed' => 'emerald',
        'cancelled' => 'red',
        'completed' => 'violet',
        'blocked'   => 'gray',
    ];

    /**
     * Genera un localizador único con el formato MTXCITA-XXXXXXXX.
     */
    public static function generateLocalizador(): string
    {
        do {
            $localizador = 'MTXCITA-' . strtoupper(Str::random(8));
        } while (self::where('localizador', $localizador)->exists());

        return $localizador;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(AppointmentVisitor::class, 'visitor_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'expediente_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Datetime completo combinando fecha + hora.
     */
    public function getAppointmentDatetimeAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->appointment_time);
    }

    /**
     * Datetime de fin de la cita.
     */
    public function getEndDatetimeAttribute(): \Carbon\Carbon
    {
        return $this->appointment_datetime->addMinutes($this->slot_duration_minutes);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                     ->whereNotIn('status', ['cancelled', 'blocked'])
                     ->orderBy('appointment_date')
                     ->orderBy('appointment_time');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('appointment_date', $date);
    }
}
