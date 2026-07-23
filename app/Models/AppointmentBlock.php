<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bloque de tiempo de cita.
 *
 * Representa un intervalo de tiempo reservado para un servicio
 * específico de un usuario, con opción de notificar a los
 * afectados por la reserva.
 *
 * Campos clave:
 * - user_id: ID del usuario propietario del bloque
 * - service_id: ID del servicio reservado
 * - start_datetime: Fecha y hora de inicio del bloque
 * - end_datetime: Fecha y hora de fin del bloque
 * - reason: Motivo del bloqueo
 * - notify_affected: Si se debe notificar a los afectados
 *
 * @property-read int $user_id
 * @property-read int $service_id
 * @property-read \Carbon\Carbon $start_datetime
 * @property-read \Carbon\Carbon $end_datetime
 * @property-read string|null $reason
 * @property-read bool $notify_affected
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\AppointmentService $service
 *
 * @mixin Builder
 */
class AppointmentBlock extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'notify_affected',
    ];

    protected $casts = [
        'start_datetime'   => 'datetime',
        'end_datetime'     => 'datetime',
        'notify_affected'  => 'boolean',
    ];

    /**
     * Relación de pertenencia al usuario propietario del bloque.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al servicio reservado.
     *
     * @return BelongsTo<\App\Models\AppointmentService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    /**
     * Scope: bloques activos (cuyo end_datetime >= ahora).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('end_datetime', '>=', now());
    }
}
