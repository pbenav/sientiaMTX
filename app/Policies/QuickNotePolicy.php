<?php

namespace App\Policies;

use App\Models\QuickNote;
use App\Models\User;

class QuickNotePolicy
{
    public function view(User $user, QuickNote $quickNote): bool
    {
        return $user->id === $quickNote->user_id;
    }

    public function update(User $user, QuickNote $quickNote): bool
    {
        return $user->id === $quickNote->user_id;
    }

    public function delete(User $user, QuickNote $quickNote): bool
    {
        return $user->id === $quickNote->user_id;
    }
}
