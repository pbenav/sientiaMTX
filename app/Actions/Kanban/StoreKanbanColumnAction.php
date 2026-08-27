<?php

namespace App\Actions\Kanban;

use App\Models\Activity;
use App\Models\KanbanColumn;
use App\Models\Team;
use Illuminate\Http\Request;

class StoreKanbanColumnAction
{
    /**
     * Creates a new custom Kanban column for a team.
     *
     * @param Team $team
     * @param Request $request
     * @return array{success: bool, column: \App\Models\KanbanColumn}
     */
    public function execute(Team $team, Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $maxOrder = $team->kanbanColumns()->max('order_index') ?? 0;

        $column = $team->kanbanColumns()->create([
            'title' => $validated['title'],
            'color' => $validated['color'] ?? '#f9fafb',
            'order_index' => $maxOrder + 1,
            'type' => 'custom',
        ]);

        return [
            'success' => true,
            'column' => $column,
        ];
    }
}
