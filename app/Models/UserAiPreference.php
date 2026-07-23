<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Preferencia de IA del usuario con claves API encriptadas.
 *
 * Almacena las preferencias de integración de IA de un usuario,
 * incluyendo proveedor por defecto, modelo, configuración de
 * privacidad, seguimiento de estado de ánimo y claves API encriptadas.
 *
 * Campos clave:
 * - user_id: ID del usuario propietario de la preferencia
 * - team_id: ID del equipo al que pertenece la preferencia
 * - default_provider: Proveedor de IA por defecto (ej: "openai", "anthropic")
 * - ai_model: Modelo de IA seleccionado
 * - smart_matching_opt_in: Si el usuario optó por matching inteligente
 * - mood_tracking_enabled: Si el seguimiento de estado de ánimo está habilitado
 * - ai_settings: Configuración de IA en formato array
 * - api_key: Clave API encriptada del usuario
 *
 * @property-read int $user_id
 * @property-read int|null $team_id
 * @property-read string $default_provider
 * @property-read string|null $ai_model
 * @property-read bool $smart_matching_opt_in
 * @property-read bool $mood_tracking_enabled
 * @property-read array $ai_settings
 *
 * @property-read string|null $api_key
 *
 * @property-read \App\Models\Team|null $team
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class UserAiPreference extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'default_provider',
        'ai_model',
        'smart_matching_opt_in',
        'mood_tracking_enabled',
        'ai_settings',
    ];

    /**
     * La api_key se excluye de $fillable para prevenir mass assignment.
     * Debe establecerse siempre a través del mutador setApiKeyAttribute()
     * que se encarga de la encriptación.
     *
     * @var array
     */
    protected $guarded = ['id', 'user_id', 'team_id', 'created_at', 'updated_at'];

    /**
     * Relación de pertenencia al equipo de la preferencia de IA.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    protected $casts = [
        'smart_matching_opt_in' => 'boolean',
        'mood_tracking_enabled' => 'boolean',
        'ai_settings' => 'array',
        // 'api_key' => 'encrypted', // Quitamos el cast automático que rompe la app
    ];

    /**
     * Accesor manual con seguridad ante fallos de desencriptación (The MAC is invalid)
     *
     * @param string $value
     * @return string|null
     */
    public function getApiKeyAttribute($value)
    {
        if (empty($value)) return null;

        try {
            return decrypt($value);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Error desencriptando API Key para preferencia {$this->id}: " . $e->getMessage());
            return null; // Devolvemos null para que el usuario pueda re-introducirla
        }
    }

    /**
     * Mutador manual para asegurar que se guarde encriptada
     *
     * @param string|null $value
     * @return void
     */
    public function setApiKeyAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['api_key'] = null;
        } else {
            $this->attributes['api_key'] = encrypt($value);
        }
    }

    /**
     * Relación de pertenencia al usuario propietario de la preferencia.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
