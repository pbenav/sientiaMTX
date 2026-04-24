<?php

namespace App\Http\Controllers;

use App\Models\KanbanColumn;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AwardsGamification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class KanbanController extends Controller
{
    use AuthorizesRequests, AwardsGamification;

    public function index(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $this->ensureDefaultColumnsExist($team);

        $user = auth()->user();
        $isManager = $team->isManager($user);

        // Sync tasks that don't have a column yet (Only operational tasks, no templates)
        $team->tasks()
            ->whereNull('kanban_column_id')
            ->operationalForKanban($user, $team)
            ->where('is_archived', false)
            ->get()->each(function ($task) {
                $task->syncKanbanColumn();
            });

        $columns = $team->kanbanColumns()
            ->with(['tasks' => function ($query) use ($team, $user, $isManager) {
                $query->visibleTo($user, $isManager)
                      ->operationalForKanban($user, $team)
                      ->where('is_archived', false)
                      ->where('is_archived', false)
                      ->orderBy('kanban_order', 'asc')
                      ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                      ->orderByRaw("FIELD(status, 'pending', 'blocked', 'in_progress', 'completed', 'cancelled') ASC")
                      ->orderBy('progress_percentage', 'desc');
            }])
            ->orderBy('order_index', 'asc')
            ->get();

        // Ensure columns have a default color if null
        $columns->each(function ($column) {
            if (!$column->color) {
                $column->color = match($column->type) {
                    'todo' => '#fee2e2',
                    'in_progress' => '#dbeafe',
                    'done' => '#dcfce7',
                    default => '#f9fafb',
                };
                $column->save();
            }
        });

        $completedTasks = $team->tasks()
            ->visibleTo($user, $isManager)
            ->where('is_archived', true)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        $hideCompleted = session('hide_completed_tasks', true);

        return view('tasks.kanban', compact('team', 'columns', 'completedTasks', 'hideCompleted'));
    }

    public function update(Request $request, Team $team, Task $task)
    {
        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['success' => false, 'message' => __('teams.unauthorized_access')], 403);
        }
        $this->authorize('update', $task);
        $oldStatus = $task->status;

        $validated = $request->validate([
            'kanban_column_id' => 'required|exists:kanban_columns,id',
            'kanban_order' => 'nullable|integer',
        ]);

        $column = KanbanColumn::findOrFail($validated['kanban_column_id']);

        // Check if column belongs to the team
        if ($column->team_id !== $team->id) {
            abort(403);
        }

        $oldColumnId = $task->kanban_column_id;
        $task->kanban_column_id = $column->id;
        $task->kanban_order = $validated['kanban_order'] ?? 0;

        // Bidirectional sync: Update progress/status based on column type
        if ($column->type === 'todo') {
            $task->progress_percentage = 0;
            $task->status = 'pending';
        } elseif ($column->type === 'done') {
            $task->progress_percentage = 100;
            $task->status = 'completed';
        } elseif ($column->type === 'in_progress' || $column->type === 'custom') {
            if ($column->default_progress !== null) {
                $task->progress_percentage = $column->default_progress;
            } else {
                // If moving to in_progress from todo, set to 10% if it was 0
                if ($task->progress_percentage == 0) {
                    $task->progress_percentage = 10;
                } elseif ($task->progress_percentage == 100) {
                    $task->progress_percentage = 90;
                }
            }
            
            if ($task->status === 'completed' || $task->status === 'pending') {
                $task->status = 'in_progress';
            }
        }

        $task->save();
        
        // Gamification: Award points if newly completed via Kanban
        if ($task->status === 'completed' && $oldStatus !== 'completed') {
            $this->awardGamificationPoints($task);
            $task->notifyCoordinatorsIfCompleted();
        }
        
        // --- Parent sync (Architectural requirement) ---
        if ($task->parent_id) {
            $currentParent = $task->parent;
            while ($currentParent) {
                // Update Parent's aggregate progress (calculated by its subtasks)
                // This ensures consistency across all board/list views
                $currentParent->update(['progress_percentage' => $currentParent->progress]);
                $currentParent->syncKanbanColumn(); // Ensure parent moves to the correct Kanban column (todo -> in_progress -> done)
                $currentParent = $currentParent->parent;
            }
        }

        // Log history if column changed
        if ($oldColumnId != $task->kanban_column_id) {
            $task->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'moved_column',
                'old_values' => ['kanban_column_id' => $oldColumnId],
                'new_values' => ['kanban_column_id' => $task->kanban_column_id, 'status' => $task->status, 'progress' => $task->progress_percentage],
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $task->status,
            'progress' => $task->progress_percentage
        ]);
    }

    public function updateColumn(Request $request, Team $team, KanbanColumn $column)
    {
        if ($column->team_id !== $team->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $column->update($validated);

        return response()->json(['success' => true]);
    }

    public function storeColumn(Request $request, Team $team)
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

        return response()->json([
            'success' => true,
            'column' => $column
        ]);
    }

    public function updateTasksOrder(Request $request, Team $team)
    {
        $validated = $request->validate([
            'column_id' => 'required|exists:kanban_columns,id',
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.kanban_order' => 'required|integer',
            'moved_task_id' => 'nullable|exists:tasks,id'
        ]);

        $column = KanbanColumn::findOrFail($validated['column_id']);

        foreach ($validated['tasks'] as $taskData) {
            $task = Task::where('id', $taskData['id'])
                ->where('team_id', $team->id)
                ->first();

            if (!$task) continue;

            $oldColumnId = $task->kanban_column_id;
            $task->kanban_order = $taskData['kanban_order'];
            $task->kanban_column_id = $column->id;

            // If this is the task that was just moved, trigger the status/progress logic
            if ($validated['moved_task_id'] == $task->id && $oldColumnId != $column->id) {
                if ($column->type === 'todo') {
                    $task->progress_percentage = 0;
                    $task->status = 'pending';
                } elseif ($column->type === 'done') {
                    $task->progress_percentage = 100;
                    $task->status = 'completed';
                } elseif ($column->type === 'in_progress' || $column->type === 'custom') {
                    if ($column->default_progress !== null) {
                        $task->progress_percentage = $column->default_progress;
                    } elseif ($task->progress_percentage == 0) {
                        $task->progress_percentage = 10;
                    }
                    if ($task->status === 'completed' || $task->status === 'pending') {
                        $task->status = 'in_progress';
                    }
                }

                // Log history for move
                $task->histories()->create([
                    'user_id' => auth()->id(),
                    'action' => 'moved_column',
                    'old_values' => ['kanban_column_id' => $oldColumnId],
                    'new_values' => [
                        'kanban_column_id' => $task->kanban_column_id, 
                        'status' => $task->status, 
                        'progress' => $task->progress_percentage
                    ],
                ]);
            }

            $oldStatus = $task->getOriginal('status');
            $task->save();

            // Gamification: Award points if newly completed via Kanban (drag to Done)
            if ($task->status === 'completed' && $oldStatus !== 'completed') {
                $this->awardGamificationPoints($task);
                $task->notifyCoordinatorsIfCompleted();
            }

            if ($validated['moved_task_id'] == $task->id) {
                $movedTaskProgress = $task->progress_percentage;
            }
        }

        return response()->json([
            'success' => true,
            'progress' => $movedTaskProgress ?? null
        ]);
    }

    public function updateColumnOrder(Request $request, Team $team)
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
    
        return response()->json(['success' => true]);
    }

    public function destroyColumn(Request $request, Team $team, KanbanColumn $column)
    {
        if ($column->team_id !== $team->id) {
            abort(403);
        }

        // Don't allow deleting default columns
        if (in_array($column->type, ['todo', 'in_progress', 'done'])) {
            return response()->json([
                'success' => false, 
                'message' => 'No puedes eliminar columnas base del sistema.'
            ], 422);
        }

        // Move tasks to the first 'todo' column to prevent loss
        $defaultColumn = $team->kanbanColumns()->where('type', 'todo')->orderBy('order_index', 'asc')->first();
        
        if ($defaultColumn) {
            $column->tasks()->update(['kanban_column_id' => $defaultColumn->id]);
        }

        $column->delete();

        return response()->json(['success' => true]);
    }

    protected function ensureDefaultColumnsExist(Team $team)
    {
        if ($team->kanbanColumns()->count() === 0) {
            $defaults = [
                ['title' => __('tasks.statuses.pending'), 'type' => 'todo', 'order_index' => 1, 'default_progress' => 0, 'color' => '#fee2e2'], 
                ['title' => __('tasks.statuses.in_progress'), 'type' => 'in_progress', 'order_index' => 2, 'default_progress' => 50, 'color' => '#dbeafe'], 
                ['title' => __('tasks.statuses.completed'), 'type' => 'done', 'order_index' => 3, 'default_progress' => 100, 'color' => '#dcfce7'],
            ];

            foreach ($defaults as $default) {
                $team->kanbanColumns()->create($default);
            }
        }
    }
}
