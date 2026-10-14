<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Policies;

use App\Models\Team;
use App\Models\TeamFolder;
use App\Models\User;

class TeamFolderPolicy
{
    public function view(User $user, TeamFolder $folder): bool
    {
        if (!$this->isTeamMember($user, $folder->team)) {
            return false;
        }

        return $folder->isVisibleTo($user, $folder->team->isManager($user));
    }

    public function create(User $user, Team $team): bool
    {
        return $this->isTeamMember($user, $team);
    }

    public function update(User $user, TeamFolder $folder): bool
    {
        if (!$this->isTeamMember($user, $folder->team)) {
            return false;
        }

        if ($folder->isPrivate()) {
            return $folder->created_by_id === $user->id || $folder->team->isManager($user);
        }

        return true;
    }

    public function delete(User $user, TeamFolder $folder): bool
    {
        if ($folder->created_by_id === $user->id) {
            return true;
        }

        return $folder->team->isManager($user);
    }

    protected function isTeamMember(User $user, Team $team): bool
    {
        if ($team->user_id === $user->id) {
            return true;
        }

        return $team->users()->where('users.id', $user->id)->exists();
    }
}

