<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasDemoMasking;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\HasPushSubscriptions;

use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use App\Traits\UserPresence;
use App\Traits\UserTeamContext;
use App\Traits\UserAiStats;
use App\Traits\UserProfile;
use App\Traits\UserStorage;
use App\Traits\UserNotifications;
/**
 * Usuario / User: entidad principal del sistema con autenticación, roles, equipos y preferencias.
 *
 * Traits incorporados:
 * - UserPresence: estado en línea y tiempo activo
 * - UserTeamContext: contexto de equipo favorito
 * - UserAiStats: estadísticas de uso de IA
 * - UserProfile: perfil y preferencias de visualización
 * - UserStorage: gestión de almacenamiento personal
 * - UserNotifications: configuración de notificaciones
 */
class User extends Authenticatable implements HasLocalePreference, PasskeyUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions, PasskeyAuthenticatable, HasDemoMasking,
        UserPresence, UserTeamContext, UserAiStats, UserProfile, UserStorage, UserNotifications;

    /**
     * Al eliminar un usuario no admin, también elimina los equipos que creó.
     */
    protected static function booted()
    {
        static::deleting(function ($user) {
            if (!$user->is_admin && $user->email !== 'demo@sientia.com') {
                foreach ($user->createdTeams as $team) {
                    $team->forceDelete();
                }
            }
        });
    }

    /**
     * Atributos sensibles que se enmascaran en modo Demo.
     *
     * @var array<string, string> Clave => tipo de máscara
     */
    protected array $demoSensitiveAttributes = [
        'name'               => 'name',
        'email'              => 'email',
        'telegram_chat_id'   => 'id',
        'telegram_username'  => 'name',
        'working_area_name'  => 'text',
        'last_ip'            => 'token',
    ];

    /**
     * Atributos protegidos (no asignables masivamente).
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'is_admin'];

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'favorite_team_id',
        'name',
        'email',
        'profile_photo_path',
        'password',
        'locale',
        'timezone',
        'is_approved',
        'invitations_left',
        'theme',
        'layout',
        'google_id',
        'google_token',
        'google_refresh_token',
        'disk_quota',
        'disk_used',
        'show_welcome_messages',
        'privacy_policy_accepted_at',
        'terms_accepted_at',
        'marketing_accepted_at',
        'notification_settings',
        'telegram_chat_id',
        'telegram_username',
        'resilience_points',
        'experience_points',
        'energy_level',
        'working_area_name',
        'location_lat',
        'location_lng',
        'impact_radius',
        'work_start_time',
        'work_end_time',
        'work_start_time_1',
        'work_end_time_1',
        'work_days_1',
        'work_start_time_2',
        'work_end_time_2',
        'work_days_2',
        'sync_with_cth',
        'cth_api_url',
        'cth_api_token',
        'cth_user_code',
        'cth_work_center_code',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_method',
        'last_ip',
    ];

    /**
     * Atributos ocultos para serialización (nunca exponer en API).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
        'cth_api_token',
        'two_factor_secret',
    ];

    /**
     * Casting de atributos a tipos nativos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'privacy_policy_accepted_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'marketing_accepted_at' => 'datetime',
            'notification_settings' => 'array',
            'resilience_points' => 'integer',
            'experience_points' => 'integer',
            'energy_level' => 'integer',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'inactive_warning_sent_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'work_days_1' => 'array',
            'work_days_2' => 'array',
            'sync_with_cth' => 'boolean',
        ];
    }

    /**
     * Equipo favorito del usuario (contexto por defecto).
     */
    public function favoriteTeam()
    {
        return $this->belongsTo(Team::class, 'favorite_team_id');
    }

    /**
     * Equipos a los que pertenece el usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Team> $teams
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot('role_id', 'sort_order', 'google_id', 'google_email', 'google_token', 'google_refresh_token', 'allow_appointments', 'allow_microsites')
            ->withTimestamps();
    }

    /**
     * Grupos a los que pertenece el usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withTimestamps();
    }

    /**
     * Equipos creados por este usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Team> $createdTeams
     */
    public function createdTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by_id');
    }

    /**
     * Grupos de chat privados del usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, ChatGroup> $chatGroups
     */
    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * Configuraciones de citas del usuario.
     */
    public function appointmentSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentSettings::class);
    }

    /**
     * Obtiene la configuración de citas del usuario para un equipo específico.
     *
     * @param  Team|int  $team  Equipo o su ID
     * @return AppointmentSettings|null
     */
    public function appointmentSettingsForTeam($team)
    {
        $teamId = $team instanceof Team ? $team->id : $team;
        return $this->appointmentSettings()->where('team_id', $teamId)->first();
    }

    /**
     * Servicios configurados para citas del usuario.
     */
    public function appointmentServices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    /**
     * Citas del usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Appointment> $appointments
     */
    public function appointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Bloques de citas del usuario.
     */
    public function appointmentBlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentBlock::class);
    }

    /**
     * Tareas asignadas al usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $assignedTasks
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    /**
     * Tareas creadas por este usuario.
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $createdTasks
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_id');
    }

    /**
     * Historial de cambios de tareas del usuario.
     */
    public function taskHistories(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    /**
     * Asignaciones de tareas del usuario.
     */
    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Invitaciones pendientes para este usuario (por email).
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'email', 'email');
    }

    /**
     * Sesiones activas del usuario.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Locale preferido para localización.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    /**
     * Adjuntos subidos por el usuario.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Hilos de foro creados por el usuario.
     */
    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    /**
     * Mensajes de foro del usuario.
     */
    public function forumMessages(): HasMany
    {
        return $this->hasMany(ForumMessage::class);
    }

    /**
     * Registros de tiempo del usuario.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Notas rápidas del usuario.
     */
    public function quickNotes(): HasMany
    {
        return $this->hasMany(QuickNote::class);
    }

    /**
     * Habilidades del usuario (gamificación).
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Skill> $skills
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')->withPivot('level', 'total_xp')->withTimestamps();
    }

    /**
     * Kudos recibidos por el usuario.
     */
    public function receivedKudos(): HasMany
    {
        return $this->hasMany(Kudo::class, 'to_user_id');
    }

    /**
     * Kudos otorgados por el usuario.
     */
    public function givenKudos(): HasMany
    {
        return $this->hasMany(Kudo::class, 'from_user_id');
    }

    /**
     * Registros de gamificación del usuario.
     */
    public function gamificationLogs(): HasMany
    {
        return $this->hasMany(GamificationLog::class);
    }

    /**
     * Preferencias de IA del usuario.
     */
    public function aiPreferences(): HasMany
    {
        return $this->hasMany(UserAiPreference::class);
    }

    /**
     * Preferencia global de IA (sin equipo).
     */
    public function aiPreference()
    {
        // Fallback global (team_id null)
        return $this->hasOne(UserAiPreference::class)->whereNull('team_id');
    }

    /**
     * Registros de estado de ánimo del usuario.
     */
    public function moodLogs(): HasMany
    {
        return $this->hasMany(UserMoodLog::class);
    }

    /**
     * Mensajes de chat IA del usuario.
     */
    public function aiChatMessages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class);
    }

    /**
     * Notas privadas de tareas del usuario.
     */
    public function taskPrivateNotes(): HasMany
    {
        return $this->hasMany(TaskPrivateNote::class);
    }

    /**
     * Calificaciones de tareas del usuario.
     */
    public function taskRatings(): HasMany
    {
        return $this->hasMany(TaskRating::class);
    }

    /**
     * Registros de seguridad del usuario.
     */
    public function securityLogs(): HasMany
    {
        return $this->hasMany(SecurityLog::class);
    }

    /**
     * Registros de adjuntos del usuario.
     */
    public function attachmentLogs(): HasMany
    {
        return $this->hasMany(AttachmentLog::class);
    }
}
