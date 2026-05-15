<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    /**
     * Determine if the user can view the survey.
     */
    public function view(User $user, Survey $survey): bool
    {
        if ($survey->team_id === null) {
            return true;
        }
        return $user->teams()->where('team_id', $survey->team_id)->exists();
    }

    /**
     * Determine if the user can create surveys.
     */
    public function create(User $user): bool
    {
        return true; 
    }

    /**
     * Determine if the user can update the survey.
     */
    public function update(User $user, Survey $survey): bool
    {
        if ($survey->team_id === null) {
            return $user->is_admin;
        }
        return $user->id === $survey->created_by_id || $survey->team->isCoordinator($user) || $user->is_admin;
    }

    /**
     * Determine if the user can vote on the survey.
     */
    public function vote(User $user, Survey $survey): bool
    {
        $canAccess = $survey->team_id === null 
            ? true 
            : $user->teams()->where('team_id', $survey->team_id)->exists();

        return $canAccess
            && !$survey->is_closed
            && !$survey->is_expired;
    }

    /**
     * Determine if the user can delete the survey.
     */
    public function delete(User $user, Survey $survey): bool
    {
        if ($survey->team_id === null) {
            return $user->is_admin;
        }
        return $user->id === $survey->created_by_id || $survey->team->isCoordinator($user) || $user->is_admin;
    }

    /**
     * Determine if the user can duplicate the survey.
     */
    public function duplicate(User $user, Survey $survey): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Global surveys can be cloned by anyone in a team
        if ($survey->team_id === null) {
            return $user->teams()->exists();
        }

        // Team surveys can be cloned by creator or coordinator
        return $user->id === $survey->created_by_id || $survey->team->isCoordinator($user);
    }
}
