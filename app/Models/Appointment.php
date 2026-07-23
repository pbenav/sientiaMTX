<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Cita / Appointment: reserva de servicio para un usuario/visitante.
 *
 * Estados: pending, confirmed, cancelled, completed, no_show, blocked
 * Colores de estado: yellow, emerald, red, violet, rose, gray
 *
 * Campos de integración:
 * - google_event_id: ID del evento en Google Calendar
 * - google_task_id: ID de la tarea en Google Tasks
 * - custom_fields_values: array de campos personalizados de la cita
 */
class Appointment extends Model
{
    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'localizador',
        'user_id',
        'service_id',
        'visitor_id',
        'modality',
        'appointment_date',
        'appointment_time',
        'slot_duration_minutes',
        'status',
        'member_notes',
        'task_id',
        'activity_id',
        'expediente_id',
        'google_event_id',
        'google_task_id',
        'cancelled_at',
        'cancellation_reason',
        'custom_fields_values',
    ];

    /**
     * Casting de atributos.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'appointment_date'     => 'date',
        'cancelled_at'         => 'datetime',
        'custom_fields_values' => 'array',
    ];

    /**
     * Estados posibles de una cita y sus etiquetas en español.
     */
    const STATUSES = [
        'pending'   => 'Pendiente',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada',
        'completed' => 'Completada',
        'no_show'   => 'No Presentado',
        'blocked'   => 'Bloqueada',
    ];

    /**
     * Colores de Tailwind para cada estado.
     */
    const STATUS_COLORS = [
        'pending'   => 'yellow',
        'confirmed' => 'emerald',
        'cancelled' => 'red',
        'completed' => 'violet',
        'no_show'   => 'rose',
        'blocked'   => 'gray',
    ];

    /**
     * Genera un localizador único con formato MTXCITA-{8 caracteres aleatorios}.
     *
     * @return string Localizador único
     */
    public static function generateLocalizador(): string
    {
        do {
            $localizador = 'MTXCITA-' . strtoupper(Str::random(8));
        } while (self::where('localizador', $localizador)->exists());

        return $localizador;
    }

    /**
     * Usuario/miembro asociado a la cita.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Servicio de cita asociado.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    /**
     * Visitante asociado a la cita.
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(AppointmentVisitor::class, 'visitor_id');
    }

    /**
     * Tarea vinculada a la cita (opcional).
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Actividad vinculada a la cita (opcional).
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    /**
     * Expediente vinculado a la cita (opcional).
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'expediente_id');
    }

    /**
     * Etiqueta legible del estado (ej: "Pendiente", "Confirmada").
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Color de Tailwind asociado al estado.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Datetime completo combinando fecha + hora de la cita.
     */
    public function getAppointmentDatetimeAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->appointment_time);
    }

    /**
     * Datetime de fin de la cita (inicio + duración del slot).
     */
    public function getEndDatetimeAttribute(): \Carbon\Carbon
    {
        return $this->appointment_datetime->addMinutes($this->slot_duration_minutes);
    }

    /**
     * Scope: filtra citas próximas (futuras o de hoy con hora >= ahora).
     *
     * Excluye citas canceladas, bloqueadas o completadas.
     */
    public function scopeUpcoming($query)
    {
        return $query->where(function($q) {
                          $q->where('appointment_date', '>', now()->toDateString())
                            ->orWhere(function($subQ) {
                                $subQ->where('appointment_date', '=', now()->toDateString())
                                     ->where('appointment_time', '>=', now()->toTimeString());
                            });
                      })
                      ->whereNotIn('status', ['cancelled', 'blocked', 'completed'])
                      ->orderBy('appointment_date')
                      ->orderBy('appointment_time')
                      ->orderBy('created_at', 'asc');
    }

    /**
     * Scope: filtra citas por fecha exacta.
     *
     * @param  string  $date  Fecha en formato Y-m-d
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('appointment_date', $date);
    }
}
