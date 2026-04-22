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
        // En MTX, la privacidad es sagrada. Ni los administradores pueden ver 
        // tareas privadas ajenas por defecto. Forzamos a evaluar cada método.
        return null;
    }

    public function create(User $user, \App\Models\Team $team): bool
    {
        if ($team->created_by_id === $user->id) {
            return true;
        }
        
        // Cualquier miembro del equipo puede crear tareas
        return $team->members()->where('user_id', $user->id)->exists();
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

        // 2. PRIVACY RULE: If private, only creator or assigned (user/collaborator/group) can view.
        // Even coordinators are blocked if they are not part of it.
        $isCreator        = $user->id === $task->created_by_id;
        $isDirectAssigned = $user->id === $task->assigned_user_id;
        $isCollaborator   = $task->assignedTo()->where('users.id', $user->id)->exists();
        $isGroupMember    = $task->assignedGroups()->whereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->exists();
        $isAssigned = $isDirectAssigned || $isCollaborator || $isGroupMember;

        if ($task->visibility === 'private') {
            if ($isCreator || $isAssigned) {
                \Log::info("TaskPolicy@view GRANTED [private_participant] task#{$task->id} user#{$user->id} isCreator={$isCreator} isDirectAssigned={$isDirectAssigned} isCollaborator={$isCollaborator} isGroupMember={$isGroupMember}");
                return true;
            }
            \Log::warning("TaskPolicy@view DENIED [strict_private] task#{$task->id} (created_by={$task->created_by_id} assigned_user_id={$task->assigned_user_id}) user#{$user->id} isCreator={$isCreator} isDirectAssigned={$isDirectAssigned}");
            return false;
        }

        // 3. PUBLIC Tasks:
        // Coordinators, team owner or task creator can always view
        $isTeamOwner = $task->team->created_by_id === $user->id;
        $isManager   = $task->team->isManager($user);
        
        if ($isCreator || $isTeamOwner || $isManager || $isAssigned) {
            return true;
        }

        // 4. Context access: If user has a personal instance of this task, they can view it.
        // GUARD: Only applies to PUBLIC tasks — private instances protect their own visibility.
        if ($task->visibility !== 'private' && $task->instances()->where('assigned_user_id', $user->id)->exists()) {
            return true;
        }

        // 5. Project Visibility: Creators of the parent task can see their PUBLIC subtasks.
        // GUARD: NEVER applies to private tasks — private subtasks are strictly personal.
        if ($task->visibility !== 'private' && $task->parent_id && $task->parent->created_by_id === $user->id) {
            return true;
        }

        \Log::warning("TaskPolicy@view DENIED [all_checks_failed] task#{$task->id} user#{$user->id} team#{$task->team_id}");
        return false;
    }

    public function update(User $user, Task $task): bool
    {
        if (!$task->team) {
            return $user->id === $task->created_by_id;
        }

        $isCreator = $user->id === $task->created_by_id;
        $isAssigned = $user->id === $task->assigned_user_id ||
                   $task->assignedTo()->where('users.id', $user->id)->exists();

        // STRICT PRIVACY: If private, even coordinators can't update unless creator or assigned
        if ($task->visibility === 'private') {
            return $isCreator || $isAssigned;
        }

        $isManager = $task->team->isManager($user);
        $isTeamOwner = $task->team->created_by_id === $user->id;

        // RULE: Only authoritative roles can update Templates/Masters
        if ($task->is_template) {
            return $isCreator || $isTeamOwner || $isManager;
        }

        // RULE: Regular tasks/instances: Assignee, Creator, Managers, Collaborators or Public access
        return $isCreator || 
               $isTeamOwner ||
               $isManager ||
               $isAssigned;
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
