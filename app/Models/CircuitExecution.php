<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Instancia activa de tramitación / circuito de aprobación.
 *
 * Se asocia polimórficamente a una Actividad o a un Expediente.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $circuit_template_id
 * @property string $circuitable_type
 * @property int $circuitable_id
 * @property string $title
 * @property int $initiated_by_id
 * @property int|null $current_step_id
 * @property string $status ('draft', 'in_progress', 'approved', 'rejected', 'returned', 'cancelled', 'published')
 * @property string|null $notes
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\CircuitTemplate|null $template
 * @property-read \Illuminate\Database\Eloquent\Model $circuitable
 * @property-read \App\Models\User $initiator
 * @property-read \App\Models\CircuitExecutionStep|null $currentStep
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CircuitExecutionStep> $steps
 * @property-read \App\Models\CircuitPublication|null $publication
 */
class CircuitExecution extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'team_id',
        'circuit_template_id',
        'circuitable_type',
        'circuitable_id',
        'title',
        'initiated_by_id',
        'current_step_id',
        'status',
        'notes',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Elemento sobre el que se tramita el circuito (Activity o Expediente).
     */
    public function circuitable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Equipo propietario de la tramitación.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Plantilla base (si se creó a partir de una plantilla).
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CircuitTemplate::class, 'circuit_template_id');
    }

    /**
     * Usuario que inició la tramitación.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    /**
     * Paso actual en el que se encuentra la tramitación.
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(CircuitExecutionStep::class, 'current_step_id');
    }

    /**
     * Pasos de esta tramitación ordenados secuencialmente.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(CircuitExecutionStep::class)->orderBy('order', 'asc');
    }

    /**
     * Registro de publicación asociado (si fue publicado en el tablón edictal).
     */
    public function publication(): HasOne
    {
        return $this->hasOne(CircuitPublication::class);
    }

    /**
     * Porcentaje de progreso basado en los pasos aprobados/completados.
     */
    public function getProgressPercentageAttribute(): int
    {
        $total = $this->steps->count();
        if ($total === 0) {
            return $this->status === self::STATUS_APPROVED ? 100 : 0;
        }

        $completed = $this->steps->whereIn('status', [CircuitExecutionStep::STATUS_APPROVED, CircuitExecutionStep::STATUS_SKIPPED])->count();
        return (int) round(($completed / $total) * 100);
    }

    /**
     * Determina si la tramitación está activa.
     */
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_RETURNED], true);
    }

    /**
     * Comprueba si un usuario determinado tiene potestad para actuar en el paso actual.
     */
    public function canUserAct(User $user): bool
    {
        if (!$this->is_active || !$this->currentStep) {
            return false;
        }

        return $this->currentStep->canUserAct($user, $this);
    }

    /**
     * Scope para tramitaciones activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_IN_PROGRESS, self::STATUS_RETURNED]);
    }

    /**
     * Scope para tramitaciones pendientes de la acción de un usuario concreto.
     */
    public function scopePendingForUser(Builder $query, User $user, ?int $teamId = null): Builder
    {
        return $query->whereIn('circuit_executions.status', [self::STATUS_IN_PROGRESS, self::STATUS_RETURNED])
            ->when($teamId, fn($q) => $q->where('circuit_executions.team_id', $teamId))
            ->whereHas('currentStep', function (Builder $stepQuery) use ($user) {
                $stepQuery->where('status', CircuitExecutionStep::STATUS_PENDING)
                    ->where(function (Builder $sub) use ($user) {
                        // Asignación directa a usuario
                        $sub->where('assigned_to_user_id', $user->id)
                            // O asignación por rol de equipo
                            ->orWhereHas('assignedRole', function (Builder $roleQuery) use ($user) {
                                $roleQuery->whereHas('teamUsers', function (Builder $tu) use ($user) {
                                    $tu->where('user_id', $user->id);
                                });
                            });
                    });
            });
    }
}
