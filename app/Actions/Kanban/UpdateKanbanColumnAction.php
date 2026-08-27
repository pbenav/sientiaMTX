<?php

namespace App\Actions\Kanban;

use App\Models\KanbanColumn;
use App\Models\Team;
use Illuminate\Http\Request;

class UpdateKanbanColumnAction
{
    /**
     * Updates a Kanban column's title and/or color.
     *
     * @param Team $team
     * @param KanbanColumn $column
     * @param Request $request
     * @return array{success: bool}
     */
    public function execute(Team $team, KanbanColumn $column, Request $request): array
    {
        if ($column->team_id !== $team->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $column->update($validated);

        return ['success' => true];
    }
}
