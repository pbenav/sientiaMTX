<?php

namespace App\Actions\Kanban;

use App\Models\Activity;
use App\Models\KanbanColumn;
use App\Models\Team;
use Illuminate\Http\Request;

class UpdateTasksOrderAction
{
    /**
     * Updates the order of multiple tasks within a Kanban column.
     *
     * @param Team $team
     * @param Request $request
     * @return array{success: bool, progress?: int}
     */
    public function execute(Team $team, Request $request): array
    {
        $validated = $request->validate([
            'column_id' => 'required|exists:kanban_columns,id',
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:activities,id',
            'tasks.*.kanban_order' => 'required|integer',
            'moved_task_id' => 'nullable|exists:activities,id',
        ]);

        $column = KanbanColumn::findOrFail($validated['column_id']);
        $syncAction = app(SyncKanbanColumnAction::class);
        $movedTaskProgress = null;

        foreach ($validated['tasks'] as $taskData) {
            $task = Activity::where('id', $taskData['id'])
                ->where('team_id', $team->id)
                ->first();

            if (!$task) {
                continue;
            }

            $oldColumnId = $task->kanban_column_id;
            $task->kanban_order = $taskData['kanban_order'];
            $task->kanban_column_id = $column->id;

            // If this is the task that was just moved, trigger the status/progress logic
            if ($validated['moved_task_id'] == $task->id && $oldColumnId != $column->id) {
                $result = $syncAction->execute($task, $column, true, $oldColumnId);
                $movedTaskProgress = $result['progress'];
            }

            $task->save();

            if ($validated['moved_task_id'] == $task->id) {
                $movedTaskProgress = $task->progress_percentage;
            }
        }

        return [
            'success' => true,
            'progress' => $movedTaskProgress,
        ];
    }
}
