<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\KanbanColumn;
use App\Models\Activity;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AwardsGamification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Traits\HandlesPersistentFilters;

class KanbanController extends Controller
{
    use AuthorizesRequests, AwardsGamification, HandlesPersistentFilters;

    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $this->ensureDefaultColumnsExist($team);

        $user = auth()->user();
        $isManager = $team->isManager($user);

        // Sync activities to ensure their column matches their actual progress (0%, 1-99%, 100%)
        $team->activities()
            ->forKanban()
            ->operationalForKanban($user, $team)
            ->notEphemeral()
            ->where('is_archived', false)
            ->get()->each(function ($activity) {
                $activity->syncKanbanColumn();
            });

        // --- Filters ---
        $filters = $this->getPersistentFilters($request, 'tasks', [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search', 'expediente_id'
        ]);

        $columns = $team->kanbanColumns()
            ->with(['activities' => function ($query) use ($team, $user, $isManager, $filters) {
                $query->with(['expediente', 'assignedUser', 'assignedTo', 'skills'])
                      ->forKanban()
                      ->visibleTo($user, $isManager)
                      ->operationalForKanban($user, $team)
                      ->notEphemeral()
                      ->where('is_archived', false)
                      ->when($filters['status'] ?? null, fn($q, $s) => $q->whereJsonContains('status->value', $s))
                      ->when($filters['priority'] ?? null, fn($q, $p) => $q->where('priority', $p))
                      ->when($filters['assigned_to'] ?? null, function($q, $a) {
                          $q->where(function ($sq) use ($a) {
                              $sq->whereHas('assignedTo', fn($sub) => $sub->where('users.id', $a))
                                 ->orWhereExists(function ($subq) use ($a) {
                                     $subq->select(\DB::raw(1))
                                          ->from('activity_task_mapping')
                                          ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                          ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                                          ->where('task_assignments.user_id', $a);
                                 });
                          });
                      })
                      ->when($filters['search'] ?? null, function($q, $s) {
                          $q->where('title', 'like', "%{$s}%")
                            ->orWhere('description', 'like', "%{$s}%");
                      })
                      ->when($filters['expediente_id'] ?? null, fn($q, $expId) => $q->where('expediente_id', $expId))
                      ->when($filters['skill_id'] ?? null, function ($q, $skillId) {
                          $q->where(function ($sq) use ($skillId) {
                              $sq->where('metadata->skill_id', $skillId)
                                 ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
                          });
                      })
                      ->when($filters['type'], function ($q, $type) {
                          if ($type === 'template') {
                              $q->where('is_template', true);
                          } elseif ($type === 'instance') {
                              $q->where('is_template', false)->whereNotNull('parent_id');
                          } elseif ($type === 'plain') {
                              $q->where('is_template', false)->whereNull('parent_id');
                          } else {
                              $q->where('type', $type);
                          }
                      })
                      ->orderBy('kanban_order', 'asc')
                      ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
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

        $completedTasks = $team->activities()
            ->with(['expediente', 'assignedUser'])
            ->whereIn('type', \App\Models\Activity::KANBAN_TYPES)
            ->visibleTo($user, $isManager)
            ->notEphemeral()
            ->where('is_archived', true)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        $hideCompleted = session('hide_completed_tasks', true);
        $members = $team->members;
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->get();
        $expedientes = $team->expedientes()->orderBy('created_at', 'desc')->get();

        return view('tasks.kanban', compact('team', 'columns', 'completedTasks', 'hideCompleted', 'filters', 'members', 'skills', 'expedientes'));
    }

    public function update(Request $request, Team $team, Activity $task)
    {
        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['success' => false, 'message' => __('teams.unauthorized_access')], 403);
        }
        $this->authorize('update', $task);
        $oldStatus = $task->status_value;

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
            $task->status = ['value' => match($task->type) {
                'document' => 'draft',
                'agreement' => 'proposed',
                'meeting'  => 'scheduled',
                default    => 'pending',
            }];
        } elseif ($column->type === 'done') {
            $task->progress_percentage = 100;
            $task->status = ['value' => match($task->type) {
                'document' => 'approved',
                'agreement' => 'accepted',
                'meeting'  => 'finished',
                default    => 'completed',
            }];
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
            
            if ($task->isCompleted() || $task->isPending()) {
                $task->status = ['value' => match($task->type) {
                    'document' => 'under_review',
                    'agreement' => 'in_debate',
                    'meeting'  => 'in_progress',
                    default    => 'in_progress',
                }];
            }
        }

        $task->save();
        
        // Gamification: Award points if newly completed via Kanban
        if ($task->isCompleted() && !in_array($oldStatus, ['completed', 'done', 'approved', 'triggered', 'accepted', 'finished'])) {
            $this->awardGamificationPoints($task);
            $task->notifyCoordinatorsIfCompleted();
        }
        
        // --- Parent sync (Architectural requirement) ---
        if ($task->parent_id) {
            $currentParent = $task->parent;
            while ($currentParent) {
                $currentParent->update(['progress_percentage' => $currentParent->progress]);
                $currentParent->syncKanbanColumn();
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
            'status' => $task->status_value,
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
            'tasks.*.id' => 'required|exists:activities,id',
            'tasks.*.kanban_order' => 'required|integer',
            'moved_task_id' => 'nullable|exists:activities,id'
        ]);

        $column = KanbanColumn::findOrFail($validated['column_id']);

        foreach ($validated['tasks'] as $taskData) {
            $task = Activity::where('id', $taskData['id'])
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
                    $task->status = ['value' => match($task->type) {
                        'document' => 'draft',
                        'agreement' => 'proposed',
                        'meeting'  => 'scheduled',
                        default    => 'pending',
                    }];
                } elseif ($column->type === 'done') {
                    $task->progress_percentage = 100;
                    $task->status = ['value' => match($task->type) {
                        'document' => 'approved',
                        'agreement' => 'accepted',
                        'meeting'  => 'finished',
                        default    => 'completed',
                    }];
                } elseif ($column->type === 'in_progress' || $column->type === 'custom') {
                    if ($column->default_progress !== null) {
                        $task->progress_percentage = $column->default_progress;
                    } elseif ($task->progress_percentage == 0) {
                        $task->progress_percentage = 10;
                    }
                    if ($task->isCompleted() || $task->isPending()) {
                        $task->status = ['value' => match($task->type) {
                            'document' => 'under_review',
                            'agreement' => 'in_debate',
                            'meeting'  => 'in_progress',
                            default    => 'in_progress',
                        }];
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
            $oldStatusValue = is_array($oldStatus) ? ($oldStatus['value'] ?? null) : $oldStatus;
            $task->save();

            // Gamification: Award points if newly completed via Kanban (drag to Done)
            if ($task->isCompleted() && !in_array($oldStatusValue, ['completed', 'done', 'approved', 'triggered', 'accepted', 'finished'])) {
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

        // Move activities to the first 'todo' column to prevent loss
        $defaultColumn = $team->kanbanColumns()->where('type', 'todo')->orderBy('order_index', 'asc')->first();
        
        if ($defaultColumn) {
            $column->activities()->update(['kanban_column_id' => $defaultColumn->id]);
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
