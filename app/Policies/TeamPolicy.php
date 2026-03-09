<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        return $team->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $team->created_by_id === $user->id
            || $this->isCoordinator($user, $team);
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $team->created_by_id === $user->id;
    }

    /**
     * Determine whether the user can view team members.
     */
    public function viewMembers(User $user, Team $team): bool
    {
        return $team->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $team->created_by_id === $user->id
            || $this->isCoordinator($user, $team);
    }

    /**
     * Check if the user is a coordinator of the team.
     */
    private function isCoordinator(User $user, Team $team): bool
    {
        $member = $team->members()->where('user_id', $user->id)->first();
        if (!$member) return false;

        return optional($member->pivot->role)->name === 'coordinator';
    }
}
