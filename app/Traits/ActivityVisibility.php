<?php

namespace App\Traits;

use App\Models\User;

trait ActivityVisibility
{
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->visibility === 'public') return true;
        if ($this->created_by_id === $user->id) return true;

        return $this->assignedTo->contains('id', $user->id)
            || $this->assignedGroups->filter(fn($g) => $g->users->contains('id', $user->id))->isNotEmpty();
    }
}
