<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Miembro de grupo de Telegram.
 *
 * Representa un miembro de un grupo de Telegram vinculado a un
 * equipo, con datos de identificación y última conexión.
 *
 * Campos clave:
 * - team_id: ID del equipo vinculado
 * - telegram_user_id: ID numérico del usuario en Telegram
 * - username: Nombre de usuario de Telegram
 * - first_name: Primer nombre
 * - last_name: Apellido
 * - last_seen_at: Fecha/hora de última conexión
 *
 * @property-read int $team_id
 * @property-read string $telegram_user_id
 * @property-read string|null $username
 * @property-read string|null $first_name
 * @property-read string|null $last_name
 * @property-read \Carbon\Carbon|null $last_seen_at
 *
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class TelegramGroupMember extends Model
{
    protected $fillable = [
        'team_id',
        'telegram_user_id',
        'username',
        'first_name',
        'last_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /**
     * Relación de pertenencia al equipo del miembro.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Atributo accesible: nombre completo del miembro.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
