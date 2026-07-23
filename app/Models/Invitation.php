<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Invitación de usuario con código de acceso.
 *
 * Representa una invitación enviada a un usuario para unirse a un
 * equipo, con código único, email y estado de uso.
 *
 * Campos clave:
 * - user_id: ID del usuario que generó la invitación
 * - team_id: ID del equipo al que se invita
 * - email: Email del usuario invitado
 * - code: Código único de acceso a la invitación
 * - used_at: Fecha/hora en que se usó la invitación
 *
 * @property-read int $user_id
 * @property-read int $team_id
 * @property-read string $email
 * @property-read string $code
 * @property-read \Carbon\Carbon|null $used_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'email',
        'code',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia al usuario que generó la invitación.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo de la invitación.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
