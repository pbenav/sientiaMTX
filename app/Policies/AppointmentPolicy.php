<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->id === $appointment->user_id) {
            return true;
        }

        $teamId = $appointment->service?->team_id ?? $appointment->service()->value('team_id');
        return $teamId ? $user->isSecretaryOf($appointment->user_id, $teamId) : false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->id === $appointment->user_id) {
            return true;
        }

        $teamId = $appointment->service?->team_id ?? $appointment->service()->value('team_id');
        return $teamId ? $user->isSecretaryOf($appointment->user_id, $teamId) : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->id === $appointment->user_id) {
            return true;
        }

        $teamId = $appointment->service?->team_id ?? $appointment->service()->value('team_id');
        return $teamId ? $user->isSecretaryOf($appointment->user_id, $teamId) : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        if ($user->id === $appointment->user_id) {
            return true;
        }

        $teamId = $appointment->service?->team_id ?? $appointment->service()->value('team_id');
        return $teamId ? $user->isSecretaryOf($appointment->user_id, $teamId) : false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        if ($user->id === $appointment->user_id) {
            return true;
        }

        $teamId = $appointment->service?->team_id ?? $appointment->service()->value('team_id');
        return $teamId ? $user->isSecretaryOf($appointment->user_id, $teamId) : false;
    }
}
