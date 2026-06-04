<?php

namespace App\Traits;

use App\Models\Survey;
use App\Models\Team;

trait ResolvesSurveyParams
{
    /**
     * Resolve and standardize $team and $survey parameters for both team-scoped and global routes.
     */
    protected function resolveParams(&$team, &$survey)
    {
        if ($survey === null) {
            $survey = $team;
            $team = null;
        }

        if (!$survey instanceof Survey) {
            $survey = Survey::findOrFail($survey);
        }

        if ($team && !$team instanceof Team) {
            $team = Team::findOrFail($team);
        }
    }
}
