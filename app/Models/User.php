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
class User extends Authenticatable implements HasLocalePreference, PasskeyUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions, PasskeyAuthenticatable, HasDemoMasking;

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
     * Sensitive attributes to mask when Demo Mode is active.
     * @var array<string, string>
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
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'is_admin'];

    /**
     * The attributes that are mass assignable.
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
     * The attributes that should be hidden for serialization.
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
     * Get the attributes that should be cast.
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
     * Determine if the user wants to be notified via a specific channel.
     */
    public function wantsNotification(string $channel, string $priority = 'medium'): bool
    {
        $settings = $this->notification_settings ?? $this->defaultNotificationSettings();
        
        // Channel disabled?
        if (!($settings[$channel] ?? false)) {
            return false;
        }

        // Safeguard: Individual WhatsApp notifications strictly require authorized permission
        if ($channel === 'whatsapp' && !($settings['whatsapp_personal_allowed'] ?? false)) {
            return false;
        }

        // Quiet hours check
        if ($this->isInQuietHours()) {
            return false;
        }

        // Priority filter (High/Critical always pass if channel is on, low only if specifically allowed)
        // For simplicity, we'll assume the channel setting implies permission unless it's a "loud" channel
        return true;
    }

    /**
     * Check if user is currently in their "Quiet Hours".
     */
    public function isInQuietHours(): bool
    {
        $settings = $this->notification_settings ?? $this->defaultNotificationSettings();
        
        if (!($settings['quiet_hours_enabled'] ?? false)) {
            return false;
        }

        $start = $settings['quiet_hours_start'] ?? '22:00';
        $end = $settings['quiet_hours_end'] ?? '08:00';
        
        $siteTimezone = config('app.timezone', 'UTC');
        $now = now($this->timezone ?? $siteTimezone)->format('H:i');

        if ($start <= $end) {
            return $now >= $start && $now < $end;
        } else {
            // Overlapping midnight (e.g. 22:00 to 08:00)
            return $now >= $start || $now < $end;
        }
    }

    /**
     * Default notification settings for new users or if not set.
     */
    public function defaultNotificationSettings(): array
    {
        $siteTimezone = config('app.timezone', 'UTC');
        return [
            'mail' => true,
            'web_push' => false,
            'telegram' => false,
            'whatsapp' => false,
            'sync_chats' => false,
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'notify_before_hours' => 2,
            'notify_scheduled_tasks' => true,
            'notify_scheduled_before_minutes' => 15,
            'morning_summary' => true,
            'morning_summary_time' => '08:00',
            'morning_summary_weekends' => true,
            'chat_sounds' => true,
            'timezone' => $siteTimezone,
        ];
    }

    // Relationships
    public function favoriteTeam()
    {
        return $this->belongsTo(Team::class, 'favorite_team_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot('role_id', 'sort_order', 'google_id', 'google_email', 'google_token', 'google_refresh_token', 'allow_appointments', 'allow_microsites')
            ->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withTimestamps();
    }

    public function createdTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by_id');
    }

    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function appointmentSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentSettings::class);
    }

    public function appointmentSettingsForTeam($team)
    {
        $teamId = $team instanceof Team ? $team->id : $team;
        return $this->appointmentSettings()->where('team_id', $teamId)->first();
    }

    public function appointmentServices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function appointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function appointmentBlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentBlock::class);
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_id');
    }

    public function taskHistories(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Get pending invitations for this user based on email.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'email', 'email');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Get the user's preferred locale.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function forumMessages(): HasMany
    {
        return $this->hasMany(ForumMessage::class);
    }

    public function hasAvailableQuota(int $bytes): bool
    {
        return ($this->disk_used + $bytes) <= $this->disk_quota;
    }

    /**
     * Get disk usage as percentage (0-100)
     */
    public function getDiskUsagePercentageAttribute(): int
    {
        if ($this->disk_quota <= 0) return 0;
        return (int) min(100, round(($this->disk_used / $this->disk_quota) * 100));
    }

    /**
     * Get the user's role name in a specific team.
     */
    public function getRole(Team $team): ?string
    {
        $membership = $this->teams()->where('team_id', $team->id)->first();
        
        if (!$membership || !$membership->pivot->role_id) {
            return null;
        }

        $role = \DB::table('team_roles')->where('id', $membership->pivot->role_id)->first();
        return $role ? $role->name : null;
    }

    /**
     * Determine if the user is a coordinator in the given team.
     */
    public function isCoordinator(Team $team): bool
    {
        return $team->isCoordinator($this);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function quickNotes(): HasMany
    {
        return $this->hasMany(QuickNote::class);
    }

    /**
     * Check if the user is currently tracking a specific task.
     */
    public function isTrackingTask(int $taskId): bool
    {
        $active = $this->activeTaskLog();
        return $active && $active->task_id === $taskId;
    }

    /**
     * Get the total seconds tracked by the user for a specific task.
     * Includes the current active session if it exists.
     */
    public function getTaskTrackingSeconds(int $taskId): int
    {
        $logs = $this->timeLogs()->where('task_id', $taskId)->get();
        
        return (int) $logs->sum(function($log) {
            if ($log->end_at) {
                return $log->start_at->diffInSeconds($log->end_at);
            }
            // If it's the active log, include seconds until now
            return $log->start_at->diffInSeconds(now());
        });
    }

    public function activeWorkdayLog(): ?TimeLog
    {
        $today = now()->startOfDay();
        if ($this->relationLoaded('timeLogs')) {
            return $this->timeLogs->where('type', 'workday')
                ->whereNull('end_at')
                ->where('start_at', '>=', $today)
                ->first();
        }
        return $this->timeLogs()
            ->where('type', 'workday')
            ->whereNull('end_at')
            ->where('start_at', '>=', $today)
            ->first();
    }

    public function activeTaskLog(): ?TimeLog
    {
        $today = now()->startOfDay();
        if ($this->relationLoaded('timeLogs')) {
            return $this->timeLogs->where('type', 'task')
                ->whereNull('end_at')
                ->where('start_at', '>=', $today)
                ->first();
        }
        return $this->timeLogs()
            ->where('type', 'task')
            ->whereNull('end_at')
            ->where('start_at', '>=', $today)
            ->first();
    }

    /**
     * Determine if the user is currently online based on session activity.
     */
    public function isOnline(): bool
    {
        return DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>', now()->subMinutes(15)->getTimestamp())
            ->exists();
    }

    /**
     * Determine if the user has any active work or task counter.
     */
    public function isWorking(): bool
    {
        return $this->timeLogs()
            ->whereIn('type', ['workday', 'task'])
            ->whereNull('end_at')
            ->where('start_at', '>=', now()->startOfDay())
            ->exists();
    }

    /**
     * Get user status info for UI indicators.
     */
    public function getStatusInfo(): array
    {
        $lastActivity = $this->last_login_at ? $this->last_activity_at : null;
        $isWorking = $this->last_login_at ? $this->isWorking() : false;
        $isOnline = $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(15));
        $isSleeping = !$isOnline && $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(60));

        if ($isWorking && $this->last_login_at) {
            return [
                'status' => 'working',
                'label' => __('En labor'),
                'color' => 'rose-500',
                'animate' => 'animate-pulse',
                'dot_class' => 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.6)]'
            ];
        }

        if ($isOnline) {
            return [
                'status' => 'online',
                'label' => __('Activo'),
                'color' => 'emerald-500',
                'animate' => 'animate-ping',
                'dot_class' => 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]'
            ];
        }

        if ($isSleeping) {
            return [
                'status' => 'sleeping',
                'label' => __('Dormido'),
                'color' => 'amber-500',
                'animate' => 'animate-pulse',
                'dot_class' => 'bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]'
            ];
        }

        return [
            'status' => 'offline',
            'label' => __('Desconectado'),
            'color' => 'gray-400',
            'animate' => '',
            'dot_class' => 'bg-gray-300 dark:bg-gray-700'
        ];
    }

    // Gamification Relationships
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')->withPivot('level', 'total_xp')->withTimestamps();
    }

    public function receivedKudos(): HasMany
    {
        return $this->hasMany(Kudo::class, 'to_user_id');
    }

    public function givenKudos(): HasMany
    {
        return $this->hasMany(Kudo::class, 'from_user_id');
    }

    public function gamificationLogs(): HasMany
    {
        return $this->hasMany(GamificationLog::class);
    }

    // AI & Wellness Relationships
    public function aiPreferences(): HasMany
    {
        return $this->hasMany(UserAiPreference::class);
    }

    public function aiPreference()
    {
        // Fallback global (team_id null)
        return $this->hasOne(UserAiPreference::class)->whereNull('team_id');
    }

    public function moodLogs(): HasMany
    {
        return $this->hasMany(UserMoodLog::class);
    }

    // Chat & Communication Relationships
    public function aiChatMessages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class);
    }

    // Notes & Ratings Relationships
    public function taskPrivateNotes(): HasMany
    {
        return $this->hasMany(TaskPrivateNote::class);
    }

    public function taskRatings(): HasMany
    {
        return $this->hasMany(TaskRating::class);
    }

    // Security & Audit Relationships
    public function securityLogs(): HasMany
    {
        return $this->hasMany(SecurityLog::class);
    }

    public function attachmentLogs(): HasMany
    {
        return $this->hasMany(AttachmentLog::class);
    }

    /**
     * Generates a 7-day statistical snapshot for AI analysis.
     */
    public function getAiContextStats(): array
    {
        $last7Days = now()->subDays(7);
        
        $completedTasksCount = $this->assignedTasks()
            ->where('status', 'completed')
            ->where('task_assignments.updated_at', '>=', $last7Days)
            ->count();

        $lateTasksCount = $this->assignedTasks()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        $workHours = $this->timeLogs()
            ->where('type', 'workday')
            ->where('start_at', '>=', $last7Days)
            ->get()
            ->sum(fn($log) => $log->end_at ? $log->start_at->diffInMinutes($log->end_at) : $log->start_at->diffInMinutes(now()));

        $avgWorkHoursPerDay = round(($workHours / 60) / 7, 2);

        $recentMood = $this->moodLogs()
            ->where('created_at', '>=', $last7Days)
            ->latest()
            ->first();

        return [
            'name' => $this->name,
            'experience' => $this->experience_points,
            'resilience' => $this->resilience_points,
            'energy_level_current' => $this->energy_level,
            'tasks_completed_7d' => $completedTasksCount,
            'tasks_late' => $lateTasksCount,
            'avg_work_hours_7d' => $avgWorkHoursPerDay,
            'total_kudos_received' => $this->receivedKudos()->count(),
            'last_mood_check' => $recentMood ? [
                'level' => $recentMood->energy_level,
                'label' => $recentMood->mood_label,
                'notes' => $recentMood->notes,
                'date' => $recentMood->created_at->diffForHumans()
            ] : null,
        ];
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function profilePhotoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            if ($this->profile_photo_path) {
                return \Illuminate\Support\Facades\Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path);
            }

            return $this->defaultProfilePhotoUrl();
        });
    }

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     *
     * @return string
     */
    protected function defaultProfilePhotoUrl()
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the disk that profile photos should be stored on.
     *
     * @return string
     */
    public function profilePhotoDisk()
    {
        return isset($_ENV['VAPOR_ARTIFACT_NAME']) ? 's3' : 'public';
    }

    /**
     * Acceso seguro al token de Google para evitar errores de descifrado en la transición.
     */
    protected function googleToken(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                try {
                    return decrypt($value, true); // Intenta descifrar
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    return null; // Si falla (viejo/corrupto), ignorar
                }
            },
            set: fn ($value) => $value ? encrypt($value, true) : null,
        );
    }

    /**
     * Acceso seguro al refresh token de Google.
     */
    protected function googleRefreshToken(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                try {
                    return decrypt($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    return null;
                }
            },
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Cita Previa permitida en al menos un equipo.
     */
    public function hasAppointmentsEnabled(): bool
    {
        return $this->teams()
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->exists();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Cita Previa permitida en un equipo específico.
     */
    public function hasAppointmentsEnabledForTeam(int $teamId): bool
    {
        return $this->teams()
            ->where('teams.id', $teamId)
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->exists();
    }

    /**
     * Obtener el primer equipo que tiene citas previas habilitadas para este usuario.
     */
    public function firstTeamWithAppointments(): ?\App\Models\Team
    {
        return $this->teams()
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->first();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Micrositios permitida en al menos un equipo.
     */
    public function hasMicrositesEnabled(): bool
    {
        return $this->teams()
            ->whereJsonContains('settings->microsites_enabled', true)
            ->wherePivot('allow_microsites', true)
            ->exists();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Micrositios permitida en un equipo específico.
     */
    public function hasMicrositesEnabledForTeam(int $teamId): bool
    {
        return $this->teams()
            ->where('teams.id', $teamId)
            ->whereJsonContains('settings->microsites_enabled', true)
            ->wherePivot('allow_microsites', true)
            ->exists();
    }

    /**
     * Obtener el primer equipo que tiene micrositios habilitados para este usuario.
     */
    public function firstTeamWithMicrosites(): ?\App\Models\Team
    {
        return $this->teams()
            ->whereJsonContains('settings->microsites_enabled', true)
            ->wherePivot('allow_microsites', true)
            ->first();
    }

}
