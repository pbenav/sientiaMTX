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
        return $task->team->members()->where('user_id', $user->id)->exists();
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
        return $user->id === $task->created_by_id || 
               $task->team->created_by_id === $user->id ||
               $task->team->isCoordinator($user);
    }
}
