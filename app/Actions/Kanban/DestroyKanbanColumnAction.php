<?php

namespace App\Actions\Kanban;

use App\Models\KanbanColumn;
use App\Models\Team;
use Illuminate\Http\Request;

class DestroyKanbanColumnAction
{
    /**
     * Deletes a custom Kanban column and reassigns its activities.
     *
     * @param Team $team
     * @param KanbanColumn $column
     * @return array{success: bool, message?: string}
     */
    public function execute(Team $team, KanbanColumn $column): array
    {
        if ($column->team_id !== $team->id) {
            abort(403);
        }

        if (in_array($column->type, ['todo', 'in_progress', 'done'])) {
            return [
                'success' => false,
                'message' => 'No puedes eliminar columnas base del sistema.',
            ];
        }

        $defaultColumn = $team->kanbanColumns()->where('type', 'todo')->orderBy('order_index', 'asc')->first();

        if ($defaultColumn) {
            $column->activities()->update(['kanban_column_id' => $defaultColumn->id]);
        }

        $column->delete();

        return ['success' => true];
    }
}
