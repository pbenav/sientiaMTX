<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_photo_path',
        'password',
        'locale',
        'timezone',
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
        'resilience_points',
        'experience_points',
        'energy_level',
        'working_area_name',
        'location_lat',
        'location_lng',
        'impact_radius',
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
            'google_token' => 'encrypted:array',
            'google_refresh_token' => 'encrypted',
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
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'notify_before_hours' => 2,
            'morning_summary' => true,
            'morning_summary_time' => '08:00',
            'timezone' => $siteTimezone,
        ];
    }

    // Relationships
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot('role_id', 'sort_order', 'google_id', 'google_email', 'google_token', 'google_refresh_token')
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

    // Time Tracking Relationships
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function activeWorkdayLog(): ?TimeLog
    {
        if ($this->relationLoaded('timeLogs')) {
            return $this->timeLogs->where('type', 'workday')->whereNull('end_at')->first();
        }
        return $this->timeLogs()->where('type', 'workday')->whereNull('end_at')->first();
    }

    public function activeTaskLog(): ?TimeLog
    {
        if ($this->relationLoaded('timeLogs')) {
            return $this->timeLogs->where('type', 'task')->whereNull('end_at')->first();
        }
        return $this->timeLogs()->where('type', 'task')->whereNull('end_at')->first();
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
            ->exists();
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
}

