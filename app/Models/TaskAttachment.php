<?php

namespace App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'storage_provider',
        'provider_file_id',
        'web_view_link',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::created(function ($attachment) {
            $team = $attachment->getTeam();
            if ($team) {
                $team->increment('disk_used', $attachment->file_size);
                // Refresh usage and check for alerts
                $team->refresh()->checkStorageAlerts();
            }
        });

        static::deleted(function ($attachment) {
            $team = $attachment->getTeam();
            if ($team) {
                $team->decrement('disk_used', max(0, $attachment->file_size));
            }
        });
    }

    public function getTeam(): ?Team
    {
        $attachable = $this->attachable;
        if (!$attachable) return null;

        if ($this->attachable_type === Task::class || $this->attachable_type === 'App\Models\Task') {
            return $attachable->team;
        }

        if ($this->attachable_type === \App\Models\ForumMessage::class || $this->attachable_type === 'App\Models\ForumMessage') {
            return $attachable->thread?->team;
        }

        return null;
    }

    /**
     * Helper relation for when the attachment belongs to a Task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'attachable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(AttachmentLog::class, 'attachment_id');
    }

    /**
     * Check if the physical file exists in storage.
     */
    public function getExistsAttribute(): bool
    {
        if ($this->storage_provider === 'google') return true; // Assume Drive files exist for now
        if (!$this->file_path) return false;
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Determine if the user can access this attachment within a specific team context.
     */
    public function canBeAccessedBy(User $user, Team $team): bool
    {
        $attachable = $this->attachable;
        if (!$attachable) return false;

        if ($this->attachable_type === 'App\Models\Task' || $this->attachable_type === Task::class) {
            if ($attachable->team_id !== $team->id) return false;
            
            $isManager = $team->isManager($user);
            $hasAccess = Task::where('id', $attachable->id)->visibleTo($user, $isManager)->exists();

            if (!$hasAccess && $attachable->children()->where('assigned_user_id', $user->id)->exists()) {
                $hasAccess = true;
            }

            return $hasAccess;
        }

        if ($this->attachable_type === \App\Models\ForumMessage::class || $this->attachable_type === 'App\Models\ForumMessage') {
            $thread = $attachable->thread;
            if (!$thread || $thread->team_id !== $team->id) return false;

            // Check if user is member of the team
            if (!$team->members()->where('users.id', $user->id)->exists()) {
                return false;
            }

            // Private message restriction
            if ($attachable->is_private) {
                $task = $thread->task;
                if (!$task) return false;

                return $task->assignedTo()->where('users.id', $user->id)->exists() || 
                       $task->created_by_id === $user->id || 
                       $task->assigned_user_id === $user->id ||
                       $team->isCoordinator($user);
            }

            return true;
        }

        return false;
    }
}
