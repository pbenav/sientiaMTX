<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Asignación de expediente a usuarios, grupos o ambos.
 *
 * Registra quién o qué grupo es responsable de un expediente,
 * junto con la fecha de asignación.
 *
 * Campos clave:
 * - expediente_id: ID del expediente asignado
 * - user_id: ID del usuario asignado (si aplica)
 * - group_id: ID del grupo asignado (si aplica)
 * - assigned_by_id: ID del usuario que realizó la asignación
 * - assigned_at: Fecha/hora de asignación
 *
 * @property-read int $expediente_id
 * @property-read int|null $user_id
 * @property-read int|null $group_id
 * @property-read int $assigned_by_id
 * @property-read \Carbon\Carbon $assigned_at
 *
 * @property-read \App\Models\Expediente $expediente
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Group $group
 * @property-read \App\Models\User $assignedBy
 *
 * @mixin Builder
 */
class ExpedienteAssignment extends Model
{
    protected $fillable = [
        'expediente_id',
        'user_id',
        'group_id',
        'assigned_by_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia al expediente asignado.
     *
     * @return BelongsTo<\App\Models\Expediente, $this>
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /**
     * Relación de pertenencia al usuario asignado.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al grupo asignado.
     *
     * @return BelongsTo<\App\Models\Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Relación de pertenencia al usuario que realizó la asignación.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
