<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Programa de citas recurrentes.
 *
 * Define horarios de disponibilidad para un usuario y servicio,
 * especificando el día de la semana, hora de inicio/fin, duración
 * de cada slot y cantidad máxima de citas por slot.
 *
 * Campos clave:
 * - user_id: ID del usuario con el horario
 * - service_id: ID del servicio al que aplica
 * - day_of_week: Día de la semana (0=Domingo, 6=Sábado)
 * - start_time: Hora de inicio del horario
 * - end_time: Hora de fin del horario
 * - slot_duration_minutes: Duración de cada slot en minutos
 * - max_per_slot: Cantidad máxima de citas por slot
 * - is_active: Si el horario está activo
 *
 * @property-read int $user_id
 * @property-read int $service_id
 * @property-read int $day_of_week
 * @property-read string $start_time
 * @property-read string $end_time
 * @property-read int|null $slot_duration_minutes
 * @property-read int|null $max_per_slot
 * @property-read bool $is_active
 * @property-read string $day_name
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\AppointmentService $service
 *
 * @mixin Builder
 */
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

    /**
     * Relación de pertenencia al usuario con el horario.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al servicio al que aplica el horario.
     *
     * @return BelongsTo<\App\Models\AppointmentService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    /**
     * Atributo accesible: nombre del día de la semana.
     *
     * @return string Nombre del día (Domingo, Lunes, etc.)
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '?';
    }

    /**
     * Scope: horarios activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
