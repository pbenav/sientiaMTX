<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        // 1. Check if user is member of the team
        if (!$task->team->members()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // 2. If public, everyone in team can view
        if ($task->visibility === 'public') {
            return true;
        }

        // 3. If private, only owner or assignees
        return $user->id === $task->created_by_id || 
               $user->id === $task->assigned_user_id ||
               $task->assignedTo()->where('users.id', $user->id)->exists() ||
               $task->assignedGroups()->whereHas('members', function($q) use ($user) {
                   $q->where('users.id', $user->id);
               })->exists();
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        $isMember = $task->team->members()->where('user_id', $user->id)->exists();
        if (!$isMember) return false;

        // Owner/Creator can always update
        if ($user->id === $task->created_by_id) return true;

        // Assignees can update (progress, etc.)
        if ($user->id === $task->assigned_user_id) return true;
        if ($task->assignedTo()->where('users.id', $user->id)->exists()) return true;
        if ($task->assignedGroups()->whereHas('members', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })->exists()) return true;

        // If public, Managers can update
        if ($task->visibility === 'public' && $task->team->isManager($user)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Only creator or team owner/manager can delete
        return $user->id === $task->created_by_id || 
               $task->team->isOwner($user) ||
               $task->team->isManager($user);
    }
}
