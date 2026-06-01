<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TelegramMessage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'telegram_chat_id',
        'whatsapp_chat_id',
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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Deep purge when force deleting
        static::forceDeleting(function (self $team) {
            DB::transaction(function () use ($team) {
                // 1. Delete tasks and their physical attachments
                foreach ($team->tasks()->withTrashed()->get() as $task) {
                    foreach ($task->attachments as $attachment) {
                        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                            Storage::disk('public')->delete($attachment->file_path);
                        }
                        $attachment->delete(); // Delete the record
                    }
                    $task->forceDelete();
                }

                // 2. Delete forum threads and messages with attachments
                foreach ($team->forumThreads as $thread) {
                    foreach ($thread->messages as $message) {
                        foreach ($message->attachments as $attachment) {
                            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                                Storage::disk('public')->delete($attachment->file_path);
                            }
                            $attachment->delete();
                        }
                        $message->delete();
                    }
                    $thread->delete();
                }

                // 3. Delete expedientes and their attachments
                foreach ($team->expedientes as $expediente) {
                    foreach ($expediente->attachments as $attachment) {
                        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                            Storage::disk('public')->delete($attachment->file_path);
                        }
                        $attachment->delete();
                    }
                    $expediente->delete();
                }

                // 4. Delete Telegram media
                $telegramMessages = TelegramMessage::where('team_id', $team->id)->get();
                foreach ($telegramMessages as $msg) {
                    $path = $msg->photo_path ?: ($msg->voice_path ?: $msg->sticker_path);
                    if ($path && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    $msg->delete();
                }

                // 5. Surveys, Groups, Skills, Kanban, Invitations, Events, Services, TimeLogs
                foreach ($team->surveys as $survey) {
                    // Deep delete survey relations if not handled by database cascade
                    foreach ($survey->questions as $question) {
                        $question->options()->delete();
                        $question->votes()->delete();
                        $question->delete();
                    }
                    $survey->delete();
                }

                $team->groups()->delete();
                $team->skills()->delete();
                $team->kanbanColumns()->delete();
                $team->invitations()->delete();
                $team->calendarEvents()->delete();
                $team->telegramGroupMembers()->delete();
                
                // Purge Services and their reports
                foreach ($team->services as $service) {
                    $service->reports()->delete();
                    $service->delete();
                }

                // Purge Time Logs associated with the team's tasks
                \App\Models\TimeLog::whereIn('task_id', $team->tasks()->withTrashed()->pluck('id'))->delete();

                // 6. Detach members
                $team->members()->detach();
            });
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
            ->withPivot('role_id', 'google_id', 'google_token', 'google_refresh_token', 'joined_at', 'allow_appointments')
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

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    public function telegramGroupMembers(): HasMany
    {
        return $this->hasMany(TelegramGroupMember::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
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
        // Always sync from real files to avoid stale DB counters
        $this->syncDiskUsed();
        $this->refresh();

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
        // 1. Calculate task attachments size (excluding Google Drive)
        $taskIds = $this->tasks()->pluck('id');
        $taskSize = TaskAttachment::where('attachable_type', Task::class)
            ->whereIn('attachable_id', $taskIds)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        // 2. Calculate forum attachments size (excluding Google Drive)
        $threadIds = $this->forumThreads()->pluck('id');
        $messageIds = ForumMessage::whereIn('forum_thread_id', $threadIds)->pluck('id');
        
        $forumSize = TaskAttachment::where('attachable_type', ForumMessage::class)
            ->whereIn('attachable_id', $messageIds)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        // 3. Calculate expediente attachments size (excluding Google Drive)
        $expedienteIds = $this->expedientes()->pluck('id');
        $expedienteSize = TaskAttachment::where('attachable_type', Expediente::class)
            ->whereIn('attachable_id', $expedienteIds)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        // 4. Calculate telegram media size
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

        $this->update(['disk_used' => (int)($taskSize + $forumSize + $expedienteSize + $telegramSize)]);
        
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
    /**
     * Get all members of the team with their current activity status info.
     */
    public function getActiveMembers()
    {
        return $this->members()
            ->with(['timeLogs' => function($q) {
                $q->whereNull('end_at')
                  ->where('start_at', '>=', now()->startOfDay());
            }])
            ->orderBy('name')
            ->get();
    }
}
