<?php

namespace App\Actions\Teams;

use App\Models\Team;

class UpdateQuadrantColorAction
{
    public function execute(Team $team, string $quadrant, string $color): void
    {
        $settings = $team->settings ?? [];
        
        if (!isset($settings['matrix_colors'])) {
            $settings['matrix_colors'] = [
                'q1' => 'border-red-500 bg-red-500/10 text-red-700',
                'q2' => 'border-blue-500 bg-blue-500/10 text-blue-700',
                'q3' => 'border-yellow-500 bg-yellow-500/10 text-yellow-700',
                'q4' => 'border-gray-500 bg-gray-500/10 text-gray-700',
            ];
        }

        $settings['matrix_colors'][$quadrant] = $color;

        $team->update(['settings' => $settings]);
    }
}
