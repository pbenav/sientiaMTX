<?php

namespace App\Models;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(AttachmentLog::class, 'attachment_id');
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
