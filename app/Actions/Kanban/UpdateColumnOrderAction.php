<?php

namespace App\Actions\Kanban;

use App\Models\Team;
use Illuminate\Http\Request;

class UpdateColumnOrderAction
{
    /**
     * Updates the order of multiple Kanban columns.
     *
     * @param Team $team
     * @param Request $request
     * @return array{success: bool}
     */
    public function execute(Team $team, Request $request): array
    {
        $validated = $request->validate([
            'columns' => 'required|array',
            'columns.*.id' => 'required|exists:kanban_columns,id',
            'columns.*.order_index' => 'required|integer',
        ]);

        foreach ($validated['columns'] as $colData) {
            $column = $team->kanbanColumns()->find($colData['id']);
            if ($column) {
                $column->update(['order_index' => $colData['order_index']]);
            }
        }

        return ['success' => true];
    }
}
