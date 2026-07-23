<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alerta de métrica con niveles de severidad.
 *
 * Representa una alerta generada por el sistema de métricas, con
 * categoría, severidad, datos adicionales y estados de lectura/resolución.
 *
 * Campos clave:
 * - user_id: ID del usuario destinatario de la alerta
 * - team_id: ID del equipo al que pertenece
 * - category: Categoría de la alerta (ej: "productivity", "wellness")
 * - severity: Nivel de severidad (ej: "info", "warning", "critical")
 * - code: Código único de la alerta
 * - title: Título de la alerta
 * - message: Mensaje descriptivo de la alerta
 * - data: Datos adicionales en formato JSON
 * - is_read: Si la alerta ha sido leída
 * - read_at: Fecha/hora de lectura
 * - resolved_at: Fecha/hora de resolución
 * - action_url: URL de acción asociada a la alerta
 *
 * @property-read int $user_id
 * @property-read int $team_id
 * @property-read string $category
 * @property-read string $severity
 * @property-read string $code
 * @property-read string $title
 * @property-read string $message
 * @property-read array $data
 * @property-read bool $is_read
 * @property-read \Carbon\Carbon|null $read_at
 * @property-read \Carbon\Carbon|null $resolved_at
 * @property-read string|null $action_url
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class MetricAlert extends Model
{
    use HasFactory;

    protected $table = 'metric_alerts';

    protected $fillable = [
        'user_id', 'team_id', 'category', 'severity', 'code',
        'title', 'message', 'data', 'is_read', 'read_at',
        'resolved_at', 'action_url',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia al usuario destinatario de la alerta.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo de la alerta.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope: alertas no leídas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: alertas por categoría.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category Categoría de la alerta
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: alertas por nivel de severidad.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $severity Nivel de severidad
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: alertas activas (no resueltas).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Marca la alerta como leída.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Marca la alerta como resuelta.
     */
    public function resolve(): void
    {
        $this->update([
            'resolved_at' => now(),
            'is_read' => true,
            'read_at' => $this->read_at ?? now(),
        ]);
    }
}
