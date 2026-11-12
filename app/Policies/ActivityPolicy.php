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

        $isMember = $activity->team->relationLoaded('members')
            ? $activity->team->members->contains('id', $user->id)
            : $activity->team->members()->where('user_id', $user->id)->exists();
        if (!$isMember) return false;

        if ($user->id === $activity->created_by_id) return true;

        // Si la actividad está vinculada a una cita previa y el usuario es secretario/a del titular en el equipo
        $appointment = $activity->relationLoaded('appointment') 
            ? $activity->appointment 
            : ($activity->appointment()->first() ?? \App\Models\Appointment::where('activity_id', $activity->id)->first());

        if ($appointment) {
            $teamId = $activity->team_id ?? $appointment->service?->team_id ?? $appointment->service()->value('team_id');
            if ($teamId && $user->isSecretaryOf($appointment->user_id, $teamId)) {
                return true;
            }
        }

        $isAssigned = $activity->relationLoaded('assignments')
            ? $activity->assignments->contains('user_id', $user->id)
            : $activity->assignments()->where('user_id', $user->id)->exists();
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

        // Si el usuario está asignado o es creador de alguna subtarea / actividad hija
        $isChildAssigned = $activity->children()
            ->where(function ($cq) use ($user) {
                $cq->where('created_by_id', $user->id)
                   ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', $user->id))
                   ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            })->exists();
        if ($isChildAssigned) return true;

        // Si el usuario está asignado a la actividad padre
        if ($activity->parent) {
            $isParentAssigned = $activity->parent->created_by_id === $user->id
                || $activity->parent->assignments()->where('user_id', $user->id)->exists()
                || $activity->parent->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists();
            if ($isParentAssigned) return true;
        }

        // Si el usuario está vinculado por conversión (actividad original o nueva convertida)
        if ($activity->convertedFromActivity) {
            $isConvAssigned = $activity->convertedFromActivity->created_by_id === $user->id
                || $activity->convertedFromActivity->assignments()->where('user_id', $user->id)->exists()
                || $activity->convertedFromActivity->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists();
            if ($isConvAssigned) return true;
        }
        if ($activity->convertedToActivity) {
            $isConvAssigned = $activity->convertedToActivity->created_by_id === $user->id
                || $activity->convertedToActivity->assignments()->where('user_id', $user->id)->exists()
                || $activity->convertedToActivity->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->exists();
            if ($isConvAssigned) return true;
        }

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

        if ($activity->team) {
            $isMember = $activity->team->relationLoaded('members')
                ? $activity->team->members->contains('id', $user->id)
                : $activity->team->members()->where('user_id', $user->id)->exists();
            if (!$isMember) return false;
        }

        // Si la actividad está vinculada a una cita previa y el usuario es secretario/a del titular en el equipo
        $appointment = $activity->relationLoaded('appointment') 
            ? $activity->appointment 
            : ($activity->appointment()->first() ?? \App\Models\Appointment::where('activity_id', $activity->id)->first());

        if ($appointment) {
            $teamId = $activity->team_id ?? $appointment->service?->team_id ?? $appointment->service()->value('team_id');
            if ($teamId && $user->isSecretaryOf($appointment->user_id, $teamId)) {
                return true;
            }
        }

        $isDirectAssigned = $activity->relationLoaded('assignments')
            ? $activity->assignments->contains('user_id', $user->id)
            : $activity->assignments()->where('user_id', $user->id)->exists();
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
