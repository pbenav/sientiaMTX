<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configuración de reserva de citas.
 *
 * Define los parámetros de configuración para el sistema de
 * reservas de un usuario dentro de un equipo, incluyendo
 * visibilidad pública, slug, duración de slots, sincronización
 * con Google Calendar, y opciones de confirmación.
 *
 * Campos clave:
 * - user_id: ID del usuario propietario de la configuración
 * - team_id: ID del equipo al que aplica
 * - public_slug: Slug público para la página de reservas
 * - display_name: Nombre que ven los visitantes
 * - is_public: Si el enlace de reservas es público
 * - welcome_text: Texto de bienvenida en la página de reservas
 * - legal_text: Texto legal / política de privacidad
 * - default_slot_duration: Duración por defecto de los slots en minutos
 * - default_max_per_slot: Máximo de citas por slot por defecto
 * - google_calendar_enabled: Si sincroniza con Google Calendar
 * - default_expediente_id: Expediente predeterminado para nuevas citas
 * - auto_create_task: Si crea una tarea automáticamente al agendar
 * - email_confirmation: Si envía confirmación por email
 * - jitsi_domain: Dominio de Jitsi para videoconferencias
 *
 * @property-read int $user_id
 * @property-read int|null $team_id
 * @property-read string|null $public_slug
 * @property-read string|null $display_name
 * @property-read bool $is_public
 * @property-read string|null $welcome_text
 * @property-read string|null $legal_text
 * @property-read int|null $default_slot_duration
 * @property-read int|null $default_max_per_slot
 * @property-read bool $google_calendar_enabled
 * @property-read int|null $default_expediente_id
 * @property-read bool $auto_create_task
 * @property-read bool $email_confirmation
 * @property-read string|null $jitsi_domain
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Team|null $team
 * @property-read \App\Models\Expediente|null $defaultExpediente
 *
 * @mixin Builder
 */
class AppointmentSettings extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'public_slug',
        'display_name',
        'is_public',
        'welcome_text',
        'legal_text',
        'default_slot_duration',
        'default_max_per_slot',
        'google_calendar_enabled',
        'default_expediente_id',
        'auto_create_task',
        'email_confirmation',
        'jitsi_domain',
    ];

    protected $casts = [
        'is_public'                => 'boolean',
        'google_calendar_enabled'  => 'boolean',
        'auto_create_task'         => 'boolean',
        'email_confirmation'       => 'boolean',
    ];

    /**
     * Relación de pertenencia al usuario propietario de la configuración.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo al que aplica la configuración.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia al expediente predeterminado para nuevas citas.
     *
     * @return BelongsTo<\App\Models\Expediente, $this>
     */
    public function defaultExpediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'default_expediente_id');
    }
}
