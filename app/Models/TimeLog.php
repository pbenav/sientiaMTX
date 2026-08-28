<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registro de seguimiento de tiempo.
 *
 * Almacena registros de tiempo dedicados a tareas/actividades, con
 * hora de inicio, fin, tipo y nota descriptiva.
 *
 * Campos clave:
 * - user_id: ID del usuario que registra el tiempo
 * - task_id: ID de la tarea/actividad asociada
 * - type: Tipo de registro ('workday' | 'task')
 * - start_at: Fecha/hora de inicio
 * - end_at: Fecha/hora de fin
 * - note: Nota descriptiva del registro
 * - is_anomalous: true si la duración supera el umbral esperado
 * - anomaly_reason: razón de la anomalía ('exceeded_schedule' | 'exceeded_threshold' | 'manual')
 * - expected_minutes: minutos de jornada esperada del usuario ese día (para estadísticas normalizadas)
 *
 * @property-read int $user_id
 * @property-read int|null $task_id
 * @property-read string $type
 * @property-read \Carbon\Carbon $start_at
 * @property-read \Carbon\Carbon|null $end_at
 * @property-read string|null $note
 * @property-read bool $is_anomalous
 * @property-read string|null $anomaly_reason
 * @property-read int|null $expected_minutes
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Activity $activity
 *
 * @mixin Builder
 */
class TimeLog extends Model
{
    use HasFactory;

    /** Umbral absoluto de horas a partir del cual siempre es anomalía, independientemente del horario */
    const ANOMALY_HARD_CAP_HOURS = 10;

    protected $fillable = [
        'user_id',
        'task_id',
        'type',
        'start_at',
        'end_at',
        'note',
        'is_anomalous',
        'anomaly_reason',
        'expected_minutes',
    ];

    protected $casts = [
        'start_at'         => 'datetime',
        'end_at'           => 'datetime',
        'is_anomalous'     => 'boolean',
        'expected_minutes' => 'integer',
    ];

    // ─────────────────────────────────────────────
    //  Relaciones
    // ─────────────────────────────────────────────

    /**
     * Relación de pertenencia al usuario que registra el tiempo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia a la actividad asociada al registro de tiempo.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'task_id');
    }

    /**
     * Relación de pertenencia a la tarea asociada (obsoleta).
     *
     * @deprecated Use activity() instead
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Activity, $this>
     */
    public function task()
    {
        return $this->activity();
    }

    // ─────────────────────────────────────────────
    //  Detección y gestión de anomalías
    // ─────────────────────────────────────────────

    /**
     * Calcula los minutos de jornada esperada del usuario para un día concreto,
     * sumando todos los turnos configurados en su perfil que apliquen ese día de la semana.
     *
     * Soporta hasta 2 turnos (mañana/tarde u otros configurados en el perfil).
     * Si el usuario no tiene horario configurado, usa el hard cap como referencia (10h).
     *
     * @param  User        $user
     * @param  Carbon|null $date  Día a evaluar (por defecto hoy)
     * @return int  Minutos de jornada esperada
     */
    public static function expectedMinutesForUser(User $user, ?Carbon $date = null): int
    {
        $date      = $date ?? now();
        $dayOfWeek = (int) $date->format('N'); // 1=Lunes … 7=Domingo

        $total = 0;

        // Turno 1: work_start_time_1 / work_end_time_1 / work_days_1
        // Con fallback a los campos legacy work_start_time / work_end_time
        $start1 = $user->work_start_time_1 ?? $user->work_start_time;
        $end1   = $user->work_end_time_1   ?? $user->work_end_time;
        $days1  = $user->work_days_1 ?? [];

        if ($start1 && $end1) {
            // Si no hay días configurados o el día actual está incluido, suma el turno
            if (empty($days1) || in_array($dayOfWeek, array_map('intval', (array) $days1))) {
                $s = Carbon::createFromTimeString($start1);
                $e = Carbon::createFromTimeString($end1);
                if ($e->gt($s)) {
                    $total += $s->diffInMinutes($e);
                }
            }
        }

        // Turno 2: work_start_time_2 / work_end_time_2 / work_days_2
        $start2 = $user->work_start_time_2;
        $end2   = $user->work_end_time_2;
        $days2  = $user->work_days_2 ?? [];

        if ($start2 && $end2) {
            if (empty($days2) || in_array($dayOfWeek, array_map('intval', (array) $days2))) {
                $s = Carbon::createFromTimeString($start2);
                $e = Carbon::createFromTimeString($end2);
                if ($e->gt($s)) {
                    $total += $s->diffInMinutes($e);
                }
            }
        }

        // Sin horario configurado → usamos el hard cap (600 min = 10h)
        return $total > 0 ? $total : (self::ANOMALY_HARD_CAP_HOURS * 60);
    }

    /**
     * Evalúa si este registro de jornada es anómalo y lo persiste si es necesario.
     *
     * Un registro se marca como anómalo si su duración supera el menor de:
     *   - La jornada esperada del usuario (suma de turnos del perfil) + 20% de margen
     *   - El hard cap absoluto de ANOMALY_HARD_CAP_HOURS horas
     *
     * Sólo aplica a registros de tipo 'workday' cerrados (end_at no nulo).
     *
     * @return bool  true si se marcó como anómalo
     */
    public function markAnomalousIfNeeded(): bool
    {
        if ($this->type !== 'workday' || !$this->end_at) {
            return false;
        }

        $durationMinutes = (int) $this->start_at->diffInMinutes($this->end_at);
        $hardCapMinutes  = self::ANOMALY_HARD_CAP_HOURS * 60;

        $user            = $this->user ?? User::find($this->user_id);
        $expectedMinutes = self::expectedMinutesForUser($user, $this->start_at);

        // Threshold efectivo: jornada esperada + 20% de cortesía, sin pasar del hard cap
        $threshold = min(
            (int) ($expectedMinutes * 1.20),
            $hardCapMinutes
        );

        if ($durationMinutes > $threshold) {
            $reason = $durationMinutes > $hardCapMinutes
                ? 'exceeded_threshold'  // supera el cap absoluto (>10h)
                : 'exceeded_schedule';  // supera el horario del perfil (+20%)

            $this->update([
                'is_anomalous'     => true,
                'anomaly_reason'   => $reason,
                'expected_minutes' => $expectedMinutes,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Devuelve los minutos efectivos de este registro para usar en estadísticas:
     * - Si es anómalo y tiene expected_minutes: devuelve la jornada normalizada
     * - Si está activo (sin end_at): devuelve el tiempo transcurrido hasta ahora
     * - Si no: devuelve los minutos reales
     */
    public function effectiveMinutes(): int
    {
        if (!$this->end_at) {
            return (int) $this->start_at->diffInMinutes(now());
        }

        if ($this->is_anomalous && $this->expected_minutes) {
            return $this->expected_minutes;
        }

        return (int) $this->start_at->diffInMinutes($this->end_at);
    }
}
