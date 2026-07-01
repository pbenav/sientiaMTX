<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Policies;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Pre-autorización: la privacidad es sagrada en MTX.
     * Ni los admins globales pueden ver actividades ajenas por defecto.
     */
    public function before(User $user, string $ability): ?bool
    {
        return null;
    }

    /**
     * Cualquier miembro del equipo puede crear actividades.
     */
    public function create(User $user, Team $team): bool
    {
        if ($team->created_by_id === $user->id) return true;
        return $team->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Ver una actividad:
     * - El creador siempre puede verla.
     * - Un asignado siempre puede verla.
     * - Si es pública y el usuario es miembro del equipo, puede verla.
     * - Los managers/coordinadores ven todas las públicas.
     */
    public function view(User $user, Activity $activity): bool
    {
        if (!$activity->team) {
            return $user->id === $activity->created_by_id;
        }

        $isMember = $activity->team->members()->where('user_id', $user->id)->exists();
        if (!$isMember) return false;

        if ($user->id === $activity->created_by_id) return true;

        $isAssigned = $activity->assignments()
            ->where('user_id', $user->id)
            ->exists();
        if ($isAssigned) return true;

        $isGroupMember = $activity->assignedGroups()
            ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->exists();
        if ($isGroupMember) return true;

        $isLegacyAssigned = \DB::table('activity_task_mapping')
            ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
            ->where('activity_task_mapping.activity_id', $activity->id)
            ->where(function ($a) use ($user) {
                $a->where('task_assignments.user_id', $user->id)
                  ->orWhereExists(function ($g) use ($user) {
                      $g->select(\DB::raw(1))
                        ->from('group_user')
                        ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                        ->where('group_user.user_id', $user->id);
                  });
            })->exists();

        if ($isLegacyAssigned) return true;

        // PRIVACIDAD "AL VUELO":
        // Si tiene asignados (nuevos o legacy) O si está marcada como privada, es PRIVADA.
        // Como ya verificamos arriba si el usuario actual es creador o está asignado,
        // si llegamos aquí, el usuario NO está asignado. Por tanto, si la tarea tiene
        // ALGÚN asignado, denegamos el acceso (a menos que sea manager).
        $hasAnyAssignee = $activity->assignments()->exists() || 
                          $activity->assignedGroups()->exists() || 
                          \DB::table('activity_task_mapping')
                            ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                            ->where('activity_task_mapping.activity_id', $activity->id)
                            ->exists();

        if ($hasAnyAssignee || $activity->visibility === 'private') {
            if ($activity->team->isManager($user)) {
                return $activity->visibility !== 'private';
            }
            return false;
        }

        // Si NO tiene asignados Y es pública, es PÚBLICA pura.
        // Cualquier miembro del equipo puede verla.
        if ($activity->visibility === 'public') return true;

        // Managers/coordinadores ven todas las del equipo que NO sean privadas de otros
        if ($activity->team->isManager($user)) {
            return $activity->visibility !== 'private';
        }

        return false;
    }

    /**
     * Actualizar: creador, asignados directos/grupos/legacy, o managers.
     */
    public function update(User $user, Activity $activity): bool
    {
        if ($user->id === $activity->created_by_id) return true;

        $isDirectAssigned = $activity->assignments()
            ->where('user_id', $user->id)
            ->exists();
        if ($isDirectAssigned) return true;

        $isGroupMember = $activity->assignedGroups()
            ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->exists();
        if ($isGroupMember) return true;

        $isLegacyAssigned = \DB::table('activity_task_mapping')
            ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
            ->where('activity_task_mapping.activity_id', $activity->id)
            ->where(function ($a) use ($user) {
                $a->where('task_assignments.user_id', $user->id)
                  ->orWhereExists(function ($g) use ($user) {
                      $g->select(\DB::raw(1))
                        ->from('group_user')
                        ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                        ->where('group_user.user_id', $user->id);
                  });
            })->exists();
        if ($isLegacyAssigned) return true;

        return $activity->team?->isManager($user) ?? false;
    }

    /**
     * Eliminar: solo el creador o managers/coordinadores.
     */
    public function delete(User $user, Activity $activity): bool
    {
        if ($user->id === $activity->created_by_id) return true;
        return $activity->team?->isManager($user) ?? false;
    }

    /**
     * Restaurar (soft-delete): solo el creador o managers.
     */
    public function restore(User $user, Activity $activity): bool
    {
        return $this->delete($user, $activity);
    }

    /**
     * Cambiar estado: cualquier asignado o el creador.
     */
    public function changeStatus(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }

    /**
     * Asignar/reasignar: solo managers o el creador.
     */
    public function assign(User $user, Activity $activity): bool
    {
        if ($user->id === $activity->created_by_id) return true;
        return $activity->team?->isManager($user) ?? false;
    }

    /**
     * Añadir adjuntos: cualquiera que pueda ver la actividad.
     */
    public function attach(User $user, Activity $activity): bool
    {
        return $this->view($user, $activity);
    }

    /**
     * Añadir notas internas: cualquiera que pueda ver la actividad.
     */
    public function addNote(User $user, Activity $activity): bool
    {
        return $this->view($user, $activity);
    }

    /**
     * Archivar: creador o managers.
     */
    public function archive(User $user, Activity $activity): bool
    {
        return $this->delete($user, $activity);
    }
}
