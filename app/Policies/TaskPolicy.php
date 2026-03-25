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

        // 2. Coordinators, team owner or task owner can always view
        if ($user->id === $task->created_by_id || 
            $task->team->created_by_id === $user->id ||
            $task->team->isManager($user)) {
            return true;
        }

        // 3. If public, everyone in team can view
        if ($task->visibility === 'public') {
            return true;
        }

        // 4. If private, check direct assignment or collaborators
        return $user->id === $task->assigned_user_id ||
               $task->assignedTo()->where('users.id', $user->id)->exists() ||
               $task->assignedGroups()->whereHas('users', function($q) use ($user) {
                   $q->where('users.id', $user->id);
               })->exists();
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->created_by_id || 
               $user->id === $task->assigned_user_id ||
               $task->team->created_by_id === $user->id ||
               $task->team->isCoordinator($user);
    }

    public function delete(User $user, Task $task): bool
    {
        // Only creator or team owner/manager can delete
        return $user->id === $task->created_by_id || 
               $task->team->created_by_id === $user->id ||
               $task->team->isCoordinator($user);
    }
}
