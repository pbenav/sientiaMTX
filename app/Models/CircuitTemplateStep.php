<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paso o etapa dentro de una plantilla de circuito.
 *
 * @property int $id
 * @property int $circuit_template_id
 * @property int $order
 * @property string $name
 * @property string|null $description
 * @property string $action_type ('endorsement', 'justification', 'approval', 'publication')
 * @property string $assignee_type ('specific_user', 'team_role', 'activity_responsible', 'creator', 'dynamic')
 * @property int|null $assignee_id
 * @property bool $is_mandatory
 * @property bool $allow_return
 * @property array|null $action_config
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\CircuitTemplate $template
 * @property-read \App\Models\User|null $assignedUser
 * @property-read \App\Models\TeamRole|null $assignedRole
 */
class CircuitTemplateStep extends Model
{
    use HasFactory;

    public const ACTION_ENDORSEMENT = 'endorsement';
    public const ACTION_JUSTIFICATION = 'justification';
    public const ACTION_APPROVAL = 'approval';
    public const ACTION_PUBLICATION = 'publication';

    public const ASSIGNEE_SPECIFIC_USER = 'specific_user';
    public const ASSIGNEE_TEAM_ROLE = 'team_role';
    public const ASSIGNEE_RESPONSIBLE = 'activity_responsible';
    public const ASSIGNEE_CREATOR = 'creator';
    public const ASSIGNEE_DYNAMIC = 'dynamic';

    protected $fillable = [
        'circuit_template_id',
        'order',
        'name',
        'description',
        'action_type',
        'assignee_type',
        'assignee_id',
        'is_mandatory',
        'allow_return',
        'action_config',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_mandatory' => 'boolean',
        'allow_return' => 'boolean',
        'action_config' => 'array',
    ];

    /**
     * Plantilla a la que pertenece este paso.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CircuitTemplate::class, 'circuit_template_id');
    }

    /**
     * Usuario asignado específico (cuando assignee_type es specific_user).
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Rol asignado (cuando assignee_type es team_role).
     */
    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(TeamRole::class, 'assignee_id');
    }

    /**
     * Etiqueta humana para el tipo de acción.
     */
    public function getActionTypeLabelAttribute(): string
    {
        return match ($this->action_type) {
            self::ACTION_ENDORSEMENT => __('circuits.action_types.endorsement', [], 'es') ?? 'Visto Bueno',
            self::ACTION_JUSTIFICATION => __('circuits.action_types.justification', [], 'es') ?? 'Justificación',
            self::ACTION_APPROVAL => __('circuits.action_types.approval', [], 'es') ?? 'Aprobación',
            self::ACTION_PUBLICATION => __('circuits.action_types.publication', [], 'es') ?? 'Publicación',
            default => ucfirst($this->action_type),
        };
    }
}
