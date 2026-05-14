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
        return $user->teams()->where('team_id', $survey->team_id)->exists();
    }

    /**
     * Determine if the user can create surveys.
     */
    public function create(User $user, Survey $survey): bool
    {
        return $user->isCoordinator($survey->team) || $user->is_admin;
    }

    /**
     * Determine if the user can update the survey.
     */
    public function update(User $user, Survey $survey): bool
    {
        return $user->id === $survey->created_by_id || $user->isCoordinator($survey->team) || $user->is_admin;
    }

    /**
     * Determine if the user can vote on the survey.
     */
    public function vote(User $user, Survey $survey): bool
    {
        return $user->teams()->where('team_id', $survey->team_id)->exists()
            && !$survey->is_closed
            && !$survey->is_expired;
    }

    /**
     * Determine if the user can delete the survey.
     */
    public function delete(User $user, Survey $survey): bool
    {
        return $user->id === $survey->created_by_id || $user->isCoordinator($survey->team) || $user->is_admin;
    }
}
