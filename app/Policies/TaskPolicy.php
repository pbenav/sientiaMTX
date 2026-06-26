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

        $isCreator        = $user->id === $task->created_by_id;
        $isDirectAssigned = $user->id === $task->assigned_user_id;
        $isCollaborator   = $task->assignedTo()->where('users.id', $user->id)->exists();
        $isGroupMember    = $task->assignedGroups()->whereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->exists();
        $isAssigned = $isDirectAssigned || $isCollaborator || $isGroupMember;

        $hasAssignees = $task->assigned_user_id !== null || $task->assignedTo()->count() > 0 || $task->assignedGroups()->count() > 0;
        
        $isTeamOwner = $task->team->created_by_id === $user->id;
        $isManager   = $task->team->isManager($user);

        // PRIVACIDAD "AL VUELO":
        // Si tiene asignados O si está marcada como privada, es PRIVADA.
        // Solo creador o asignados la ven (y managers si es plantilla).
        if ($hasAssignees || $task->visibility === 'private') {
            if ($isCreator || $isAssigned || ($isManager && $task->is_template)) {
                return true;
            }
            \Log::warning("TaskPolicy@view DENIED [private_task] task#{$task->id} user#{$user->id}");
            return false;
        }

        // Si NO tiene asignados Y es pública, es PÚBLICA pura.
        // Cualquier miembro del equipo puede verla.
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if (!$task->team) {
            return $user->id === $task->created_by_id;
        }

        $isCreator = $user->id === $task->created_by_id;
        $isAssigned = $user->id === $task->assigned_user_id ||
                   $task->assignedTo()->where('users.id', $user->id)->exists() ||
                   $task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists();

        $hasAssignees = $task->assigned_user_id !== null || $task->assignedTo()->count() > 0 || $task->assignedGroups()->count() > 0;

        $isManager = $task->team->isManager($user);
        $isTeamOwner = $task->team->created_by_id === $user->id;

        // PRIVACIDAD "AL VUELO" (Update):
        // Si tiene asignados O está marcada como privada, solo el creador o asignados pueden editar.
        if ($hasAssignees || $task->visibility === 'private') {
            if ($isCreator || $isAssigned || ($isManager && $task->is_template)) {
                return true;
            }
            return false;
        }

        // Si es plantilla, la gestión es permitida para creadores, asignados y managers
        if ($task->is_template) {
            return $isCreator || $isAssigned || $isTeamOwner || $isManager;
        }

        // Si es pública pura (no plantilla, no asignada), cualquiera en el equipo puede reclamarla/editarla
        return true;
    }

    public function delete(User $user, Task $task): bool
    {
        if (!$task->team) {
            return $user->id === $task->created_by_id;
        }

        $isCreator = $user->id === $task->created_by_id;
        $isDirectAssigned = $user->id === $task->assigned_user_id;
        $isCollaborator = $task->assignedTo()->where('users.id', $user->id)->exists();
        $isGroupMember = $task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists();
        $isAssigned = $isDirectAssigned || $isCollaborator || $isGroupMember;

        // Allow creator, assigned users, team owner, or coordinators to delete
        return $isCreator || $isAssigned || 
               $task->team->created_by_id === $user->id ||
               $task->team->isCoordinator($user);
    }
}
