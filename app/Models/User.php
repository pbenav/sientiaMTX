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
        'password',
        'locale',
        'timezone',
        'theme',
        'layout',
        'is_admin',
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
        
        $siteTimezone = config('app.timezone', 'Europe/Madrid');
        $now = now($this->timezone ?? $siteTimezone)->format('H:i');

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            // Overlapping midnight (e.g. 22:00 to 08:00)
            return $now >= $start || $now <= $end;
        }
    }

    /**
     * Default notification settings for new users or if not set.
     */
    public function defaultNotificationSettings(): array
    {
        $siteTimezone = config('app.timezone', 'Europe/Madrid');
        return [
            'mail' => true,
            'web_push' => false,
            'telegram' => false,
            'whatsapp' => false,
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'notify_before_hours' => 2,
            'timezone' => $siteTimezone,
        ];
    }

    // Relationships
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role_id')
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
}

