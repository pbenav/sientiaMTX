<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Informe de métricas con gráficos e insights.
 *
 * Representa un informe generado por el sistema de métricas, con
 * datos resumidos, gráficos, insights automáticos y soporte para
 * múltiples formatos y estados de generación.
 *
 * Campos clave:
 * - user_id: ID del usuario destinatario del informe
 * - team_id: ID del equipo al que pertenece
 * - type: Tipo de informe (ej: "weekly", "monthly", "custom")
 * - period_label: Etiqueta descriptiva del período
 * - period_start: Fecha de inicio del período
 * - period_end: Fecha de fin del período
 * - summary: Resumen del informe en formato array
 * - charts_data: Datos de gráficos en formato array
 * - insights: Insights automáticos en formato array
 * - status: Estado de generación (pending, completed, failed)
 * - error_message: Mensaje de error si la generación falló
 * - format: Formato del informe (pdf, json, html)
 * - file_path: Ruta del archivo generado
 * - generated_at: Fecha/hora de generación
 *
 * @property-read int $user_id
 * @property-read int $team_id
 * @property-read string $type
 * @property-read string $period_label
 * @property-read \Carbon\Carbon $period_start
 * @property-read \Carbon\Carbon $period_end
 * @property-read array $summary
 * @property-read array $charts_data
 * @property-read array $insights
 * @property-read string $status
 * @property-read string|null $error_message
 * @property-read string|null $format
 * @property-read string|null $file_path
 * @property-read \Carbon\Carbon $generated_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class MetricReport extends Model
{
    use HasFactory;

    protected $table = 'metric_reports';

    protected $fillable = [
        'user_id', 'team_id', 'type', 'period_label',
        'period_start', 'period_end', 'summary', 'charts_data',
        'insights', 'status', 'error_message', 'format',
        'file_path', 'generated_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'charts_data' => 'array',
        'insights' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia al usuario destinatario del informe.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo del informe.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope: informes por tipo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type Tipo de informe
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: informes completados.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: informes por período.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }
}
