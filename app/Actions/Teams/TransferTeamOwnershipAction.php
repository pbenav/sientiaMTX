<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;

class TransferTeamOwnershipAction
{
    public function execute(Team $team, User $newOwner, User $currentOwner): void
    {
        // Add new owner as user if not already
        if (!$team->users()->where('user_id', $newOwner->id)->exists()) {
            $team->users()->attach($newOwner->id, ['role' => 'admin']);
        } else {
            $team->users()->updateExistingPivot($newOwner->id, ['role' => 'admin']);
        }

        // Add current owner as regular user
        if (!$team->users()->where('user_id', $currentOwner->id)->exists()) {
            $team->users()->attach($currentOwner->id, ['role' => 'admin']);
        }

        // Change actual ownership
        $team->update(['user_id' => $newOwner->id]);
    }
}
