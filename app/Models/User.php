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
class User extends Authenticatable implements HasLocalePreference, PasskeyUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions, PasskeyAuthenticatable, HasDemoMasking,
        UserPresence, UserTeamContext, UserAiStats, UserProfile, UserStorage, UserNotifications;

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

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function quickNotes(): HasMany
    {
        return $this->hasMany(QuickNote::class);
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
}
