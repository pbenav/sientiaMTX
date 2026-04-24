<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'telegram_chat_id',
        'created_by_id',
        'quadrant_colors',
        'settings',
        'disk_quota',
        'disk_used',
    ];

    protected $casts = [
        'quadrant_colors' => 'array',
        'settings' => 'array',
        'disk_quota' => 'integer',
        'disk_used' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationship: A team has many tasks
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Relationship: A team has many users (members)
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot('role_id', 'google_id', 'google_token', 'google_refresh_token', 'joined_at')
            ->orderBy('name');
    }

    // Relationship: A team has many groups
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    // Relationship: A team has many calendar events
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function kanbanColumns(): HasMany
    {
        return $this->hasMany(KanbanColumn::class)->orderBy('order_index');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    // Get creator of the team
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get all coordinators for this team
     */
    public function coordinators(): BelongsToMany
    {
        return $this->members()
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')->where('name', 'coordinator');
            })
            ->orderBy('name');
    }

    /**
     * Check if a user is a coordinator for this team (Admin)
     */
    public function isCoordinator(User $user): bool
    {
        return $this->coordinators()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if a user is a manager for this team (Coordinator)
     */
    public function isManager(User $user): bool
    {
        return $this->isCoordinator($user) || $this->isModerator($user);
    }

    /**
     * Check if a user is a moderator for this team
     */
    public function isModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')
                    ->where('name', 'moderator');
            })
            ->exists();
    }

    /**
     * Check if a user is the owner of the team
     */
    public function isOwner(User $user): bool
    {
        return $this->created_by_id === $user->id;
    }
    /**
     * Get the quadrant color configuration for this team.
     */
    public function getQuadrantConfig(): array
    {
        $defaults = [
            1 => ['color' => '#ef4444', 'bg' => 'bg-red-200 border-red-400 dark:bg-red-500/25 dark:border-red-500/60', 'dot' => 'bg-red-500'],
            2 => ['color' => '#3b82f6', 'bg' => 'bg-blue-200 border-blue-400 dark:bg-blue-500/25 dark:border-blue-500/60', 'dot' => 'bg-blue-500'],
            3 => ['color' => '#f59e0b', 'bg' => 'bg-amber-200 border-amber-400 dark:bg-amber-500/25 dark:border-amber-500/60', 'dot' => 'bg-amber-500'],
            4 => ['color' => '#6b7280', 'bg' => 'bg-gray-200 border-gray-400 dark:bg-gray-500/25 dark:border-gray-500/60', 'dot' => 'bg-gray-500'],
        ];

        if (empty($this->quadrant_colors)) {
            return $defaults;
        }

        $config = $defaults;
        foreach ($this->quadrant_colors as $q => $color) {
            $q = (int)$q;
            if (isset($config[$q])) {
                $config[$q]['color'] = $color;
            }
        }

        return $config;
    }

    /**
     * Convert hex color to rgba string
     */
    public function hexToRgba($hex, $alpha = 1): string
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "rgba($r, $g, $b, $alpha)";
    }

    /**
     * Check if team has enough quota for a new file.
     */
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
     * Sync the disk_used field based on actual attachments.
     */
    public function syncDiskUsed(): void
    {
        // 1. Calculate task attachments size
        $taskIds = $this->tasks()->pluck('id');
        $taskSize = TaskAttachment::where('attachable_type', Task::class)
            ->whereIn('attachable_id', $taskIds)
            ->sum('file_size');

        // 2. Calculate forum attachments size
        $threadIds = $this->forumThreads()->pluck('id');
        $messageIds = ForumMessage::whereIn('forum_thread_id', $threadIds)->pluck('id');
        
        $forumSize = TaskAttachment::where('attachable_type', ForumMessage::class)
            ->whereIn('attachable_id', $messageIds)
            ->sum('file_size');

        // 3. Calculate telegram media size
        $telegramSize = TelegramMessage::where('team_id', $this->id)
            ->get()
            ->sum(function($msg) {
                if ($msg->file_size > 0) return $msg->file_size;
                
                // Fallback: check physical disk for old messages
                $path = $msg->photo_path ?: ($msg->voice_path ?: $msg->sticker_path);
                if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    $size = \Illuminate\Support\Facades\Storage::disk('public')->size($path);
                    // Update database to avoid re-checking disk
                    $msg->update(['file_size' => $size]);
                    return $size;
                }
                return 0;
            });

        $this->update(['disk_used' => (int)($taskSize + $forumSize + $telegramSize)]);
        
        $this->checkStorageAlerts();
    }

    /**
     * Check usage and notify coordinators if threshold is reached.
     */
    public function checkStorageAlerts(): void
    {
        $percentage = $this->disk_usage_percentage;
        
        if ($percentage >= 90) {
            $coordinators = $this->coordinators;
            
            if ($coordinators->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send(
                    $coordinators, 
                    new \App\Notifications\TeamStorageLimitReached($this, $percentage)
                );
            }
        }
    }
}
