<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paso o etapa concreta de una tramitación en curso.
 *
 * @property int $id
 * @property int $circuit_execution_id
 * @property int|null $template_step_id
 * @property int $order
 * @property string $name
 * @property string|null $description
 * @property string $action_type ('endorsement', 'justification', 'approval', 'publication')
 * @property string $assignee_type
 * @property int|null $assigned_to_user_id
 * @property int|null $assigned_to_role_id
 * @property string $status ('pending', 'in_progress', 'approved', 'rejected', 'returned', 'skipped')
 * @property int|null $action_taken_by_id
 * @property string|null $decision ('approved', 'rejected', 'returned', 'skipped')
 * @property string|null $comments
 * @property array|null $action_data
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\CircuitExecution $execution
 * @property-read \App\Models\User|null $assignedUser
 * @property-read \App\Models\TeamRole|null $assignedRole
 * @property-read \App\Models\User|null $actionUser
 */
class CircuitExecutionStep extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_SKIPPED = 'skipped';

    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';
    public const DECISION_RETURNED = 'returned';

    protected $fillable = [
        'circuit_execution_id',
        'template_step_id',
        'order',
        'name',
        'description',
        'action_type',
        'assignee_type',
        'assigned_to_user_id',
        'assigned_to_role_id',
        'status',
        'action_taken_by_id',
        'decision',
        'comments',
        'action_data',
        'completed_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'action_data' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Tramitación a la que pertenece este paso.
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(CircuitExecution::class, 'circuit_execution_id');
    }

    /**
     * Usuario asignado expresamente al paso.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Rol de equipo asignado al paso.
     */
    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(TeamRole::class, 'assigned_to_role_id');
    }

    /**
     * Usuario que ejecutó la acción (aprobó, devolvió o rechazó).
     */
    public function actionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_taken_by_id');
    }

    /**
     * Comprueba si el paso está completado.
     */
    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_SKIPPED], true);
    }

    /**
     * Comprueba si un usuario tiene permisos para resolver este paso.
     */
    public function canUserAct(User $user, ?CircuitExecution $execution = null): bool
    {
        $execution = $execution ?? $this->execution;
        if (!$execution) {
            return false;
        }

        // Si es el usuario explícitamente asignado
        if ($this->assigned_to_user_id && $this->assigned_to_user_id === $user->id) {
            return true;
        }

        // Si está asignado a un rol del equipo
        if ($this->assigned_to_role_id && $execution->team && $execution->team->exists) {
            $hasRole = $execution->team->members()
                ->where('users.id', $user->id)
                ->wherePivot('role_id', $this->assigned_to_role_id)
                ->exists();

            if ($hasRole) {
                return true;
            }
        }

        // El propietario o coordinador del equipo tiene potestad de supervisión/firma
        if ($execution->team) {
            if ($execution->team->owner_id === $user->id) {
                return true;
            }
            if ($execution->team->relationLoaded('coordinators')) {
                return $execution->team->coordinators->contains('id', $user->id);
            }
            if ($execution->team->exists) {
                return $execution->team->isCoordinator($user);
            }
        }

        return false;
    }

    /**
     * Etiqueta humana para el tipo de acción.
     */
    public function getActionTypeLabelAttribute(): string
    {
        return match ($this->action_type) {
            CircuitTemplateStep::ACTION_ENDORSEMENT => __('circuits.action_types.endorsement', [], 'es') ?? 'Visto Bueno',
            CircuitTemplateStep::ACTION_JUSTIFICATION => __('circuits.action_types.justification', [], 'es') ?? 'Justificación',
            CircuitTemplateStep::ACTION_APPROVAL => __('circuits.action_types.approval', [], 'es') ?? 'Aprobación',
            CircuitTemplateStep::ACTION_PUBLICATION => __('circuits.action_types.publication', [], 'es') ?? 'Publicación',
            default => ucfirst($this->action_type),
        };
    }
}
