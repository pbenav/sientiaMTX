<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        // If it's an orphan task (team deleted), only admins (handled by before) or the creator can see it for management
        if (!$task->team) {
            return $user->id === $task->created_by_id;
        }

        // 1. Check if user is member of the team
        $isMember = $task->team->members()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            \Log::warning("TaskPolicy@view DENIED [not_member] task#{$task->id} user#{$user->id} team#{$task->team_id}");
            return false;
        }

        // 2. Coordinators, team owner or task owner can always view
        $isCreator     = $user->id === $task->created_by_id;
        $isTeamOwner   = $task->team->created_by_id === $user->id;
        $isManager     = $task->team->isManager($user);
        if ($isCreator || $isTeamOwner || $isManager) {
            return true;
        }

        // 3. If public, everyone in team can view
        if ($task->visibility === 'public') {
            return true;
        }

        // 4. If private, check direct assignment, collaborators or group assignment
        if ($user->id === $task->assigned_user_id ||
               $task->assignedTo()->where('users.id', $user->id)->exists() ||
               $task->assignedGroups()->whereHas('users', function($q) use ($user) {
                   $q->where('users.id', $user->id);
               })->exists()) {
            return true;
        }

        // 5. Context access: If user has a personal instance of this parent task, they can view the parent for context
        if ($task->instances()->where('assigned_user_id', $user->id)->exists()) {
            return true;
        }

        // 6. Project Visibility: Owners/Creators of the parent task can always see their subtasks
        if ($task->parent_id && $task->parent->created_by_id === $user->id) {
            return true;
        }

        \Log::warning("TaskPolicy@view DENIED [all_checks_failed] task#{$task->id} user#{$user->id} team#{$task->team_id} isCreator={$isCreator} isTeamOwner={$isTeamOwner} isManager={$isManager} visibility={$task->visibility}");
        return false;
    }

    public function update(User $user, Task $task): bool
    {
        if (!$task->team) {
            return $user->id === $task->created_by_id;
        }

        $isManager = $task->team->isManager($user);
        $isCreator = $user->id === $task->created_by_id;
        $isTeamOwner = $task->team->created_by_id === $user->id;

        // RULE: Only authoritative roles can update Templates/Masters
        if ($task->is_template) {
            return $isCreator || $isTeamOwner || $isManager;
        }

        // RULE: Regular tasks/instances: Assignee, Creator, Managers, Collaborators or Public access
        return $isCreator || 
               $isTeamOwner ||
               $isManager ||
               $user->id === $task->assigned_user_id ||
               $task->assignedTo()->where('users.id', $user->id)->exists() ||
               ($task->visibility === 'public' && $task->team->members()->where('user_id', $user->id)->exists());
    }

    public function delete(User $user, Task $task): bool
    {
        if (!$task->team) {
            return $user->id === $task->created_by_id;
        }

        // Only creator or team owner/manager can delete
        return $user->id === $task->created_by_id || 
               $task->team->created_by_id === $user->id ||
               $task->team->isCoordinator($user);
    }
}
