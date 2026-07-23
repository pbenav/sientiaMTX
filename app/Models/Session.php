<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sesión de usuario del sistema.
 *
 * Modelo para la tabla de sesiones de Laravel, con datos de
 * sesión, usuario asociado y última actividad.
 *
 * Campos clave:
 * - id: ID único de la sesión (token)
 * - user_id: ID del usuario de la sesión
 * - ip_address: Dirección IP del usuario
 * -user_agent: User agent del navegador
 * - payload: Datos de la sesión en formato serializado
 * - last_activity: Marca de tiempo de última actividad
 *
 * @property-read string $id
 * @property-read int|null $user_id
 * @property-read string|null $ip_address
 * @property-read string|null $user_agent
 * @property-read string $payload
 * @property-read int $last_activity
 *
 * @property-read \App\Models\User|null $user
 *
 * @mixin Builder
 */
class Session extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model is timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_activity' => 'integer',
    ];

    /**
     * Relación de pertenencia al usuario de la sesión.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
