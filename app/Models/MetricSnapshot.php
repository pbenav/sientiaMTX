<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Instantánea de métricas con puntuaciones de bienestar y productividad.
 *
 * Representa un punto de datos en el tiempo que captura el estado
 * del equipo en materia de bienestar, productividad, engagement,
 * riesgos de burnout y diversos índices de rendimiento.
 *
 * Campos clave:
 * - uuid: Identificador único de la instantánea
 * - user_id: ID del usuario (null para snapshots de equipo)
 * - team_id: ID del equipo
 * - type: Tipo de snapshot (daily, weekly, monthly)
 * - snapshot_date: Fecha de la instantánea
 * - metrics: Métricas adicionales en formato array
 * - trends: Tendencias en formato array
 * - alerts: Alertas en formato array
 * - wellness_score: Puntuación de bienestar (0-100)
 * - productivity_score: Puntuación de productividad (0-100)
 * - engagement_score: Puntuación de engagement (0-100)
 * - mood_index: Índice de estado de ánimo
 * - stress_index: Índice de estrés
 * - energy_index: Índice de energía
 * - satisfaction_index: Índice de satisfacción
 * - burnout_risk_score: Puntuación de riesgo de burnout
 * - burnout_risk_level: Nivel de riesgo de burnout
 * - activities_completed: Cantidad de actividades completadas
 * - activities_in_progress: Cantidad de actividades en progreso
 * - activities_pending: Cantidad de actividades pendientes
 * - activities_overdue: Cantidad de actividades vencidas
 * - completion_rate: Tasa de completitud
 * - on_time_delivery: Entrega a tiempo
 * - estimation_accuracy: Precisión de estimaciones
 * - hours_logged: Horas registradas
 * - hours_overtime: Horas extra
 * - streak_days: Días consecutivos activos
 * - team_wellness_avg: Promedio de bienestar del equipo
 * - team_size: Tamaño del equipo
 * - members_overloaded: Miembros sobrecargados
 * - members_underloaded: Miembros infractivos
 * - nudge_responsiveness: Respuesta a los "nudges"
 * - work_life_balance_index: Índice de equilibrio trabajo-vida
 *
 * @property-read string $uuid
 * @property-read int|null $user_id
 * @property-read int $team_id
 * @property-read string $type
 * @property-read \Carbon\Carbon $snapshot_date
 * @property-read array $metrics
 * @property-read array $trends
 * @property-read array $alerts
 * @property-read float|null $wellness_score
 * @property-read float|null $productivity_score
 * @property-read float|null $engagement_score
 * @property-read float|null $mood_index
 * @property-read float|null $stress_index
 * @property-read float|null $energy_index
 * @property-read float|null $satisfaction_index
 * @property-read float|null $burnout_risk_score
 * @property-read string|null $burnout_risk_level
 * @property-read int $activities_completed
 * @property-read int $activities_in_progress
 * @property-read int $activities_pending
 * @property-read int $activities_overdue
 * @property-read float|null $completion_rate
 * @property-read float|null $on_time_delivery
 * @property-read float|null $estimation_accuracy
 * @property-read float|null $hours_logged
 * @property-read float|null $hours_overtime
 * @property-read int $streak_days
 * @property-read float|null $team_wellness_avg
 * @property-read int $team_size
 * @property-read int $members_overloaded
 * @property-read int $members_underloaded
 * @property-read float|null $nudge_responsiveness
 * @property-read float|null $work_life_balance_index
 *
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class MetricSnapshot extends Model
{
    use HasFactory;

    protected $table = 'metric_snapshots';

    protected $fillable = [
        'uuid', 'user_id', 'team_id', 'type', 'snapshot_date',
        'metrics', 'trends', 'alerts',
        'wellness_score', 'productivity_score', 'engagement_score',
        'mood_index', 'stress_index', 'energy_index', 'satisfaction_index',
        'burnout_risk_score', 'burnout_risk_level',
        'activities_completed', 'activities_in_progress', 'activities_pending', 'activities_overdue',
        'completion_rate', 'on_time_delivery', 'estimation_accuracy',
        'hours_logged', 'hours_overtime', 'streak_days',
        'team_wellness_avg', 'team_size', 'members_overloaded', 'members_underloaded',
        'nudge_responsiveness', 'work_life_balance_index',
    ];

    protected $casts = [
        'metrics' => 'array',
        'trends' => 'array',
        'alerts' => 'array',
        'snapshot_date' => 'date',
        'wellness_score' => 'decimal:2',
        'productivity_score' => 'decimal:2',
        'engagement_score' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'on_time_delivery' => 'decimal:2',
        'estimation_accuracy' => 'decimal:2',
        'hours_logged' => 'decimal:2',
        'hours_overtime' => 'decimal:2',
        'work_life_balance_index' => 'decimal:2',
        'team_wellness_avg' => 'decimal:2',
        'is_read' => 'boolean',
    ];

    /**
     * Relación de pertenencia al usuario de la instantánea.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo de la instantánea.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope: instantáneas por tipo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type Tipo de snapshot
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: instantáneas por fecha.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date Fecha de la instantánea
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }

    /**
     * Scope: instantáneas por usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId ID del usuario
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: instantáneas por equipo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $teamId ID del equipo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
