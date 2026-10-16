<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delegación de gestión de citas (Secretaría).
 *
 * Permite que un usuario (titular) delegue la gestión de sus citas,
 * agenda y bloqueos a otro miembro del equipo (secretario/a).
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property int $delegate_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User $delegate
 */
class AppointmentDelegation extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'delegate_id',
    ];

    /**
     * Equipo en el que opera la delegación.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Titular / dueño de la agenda.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Secretario/a delegado/a.
     */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }
}
