<?php

namespace App\Policies;

use App\Models\Expediente;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExpedientePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, $team): bool
    {
        return $user->can('view', $team);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Expediente $expediente): bool
    {
        return $user->can('view', $expediente->team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, $team): bool
    {
        return $user->can('view', $team) && ($user->isCoordinator($team) || $team->owner?->id === $user->id || $user->hasTeamPermission($team, 'create:expedientes'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Expediente $expediente): bool
    {
        return $this->delete($user, $expediente);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Expediente $expediente): bool
    {
        return $user->isCoordinator($expediente->team) 
            || $expediente->created_by_id === $user->id 
            || $expediente->team->owner?->id === $user->id;
    }
}
