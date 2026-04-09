<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GanttController extends Controller
{
    /**
     * Show the Gantt chart view for a team
     */
    public function index(Request $request, Team $team)
    {
        $members = $team->members()->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->get();

        // Get the exact task set that would be visible in the Gantt chart with current filters
        $tasks = $this->getTaskSet($request, $team);

        // Calculate Heat Bar (Action Density) for the current month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $actionHeat = [];
        $userId = auth()->id();

        // 2.1: Identify Leaf Tasks to avoid double counting effort (Master + Instance)
        $parentIds = $tasks->pluck('parent_id')->filter()->unique();
        $leafTasks = $tasks->filter(fn($t) => !$parentIds->contains($t->id));

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentDay = $startOfMonth->copy()->addDays($i - 1);
            
            // Filter leaf tasks active this specific day from the already filtered set
            $dayTasks = $leafTasks->filter(function($t) use ($currentDay) {
                $start = $t->scheduled_date ?? $t->created_at;
                $end = $t->due_date ?? $start;
                return $currentDay->between($start->startOfDay(), $end->endOfDay());
            });

            $actionHeat[$i] = [
                'weight' => $dayTasks->sum(fn($t) => $t->cognitive_load ?? 1),
                'user_weight' => $dayTasks->where('assigned_user_id', $userId)->sum(fn($t) => $t->cognitive_load ?? 1),
                'count' => $dayTasks->count(),
                'user_count' => $dayTasks->where('assigned_user_id', $userId)->count(),
            ];
        }

        return view('teams.gantt', compact('team', 'members', 'skills', 'actionHeat', 'daysInMonth'));
    }

    /**
     * Get tasks data formatted for Frappe Gantt.
     */
    public function data(Request $request, Team $team)
    {
        $tasks = $this->getTaskSet($request, $team);

        // Map to Frappe Gantt format
        $formattedTasks = $tasks->map(function (Task $task) {
            $start = $task->scheduled_date ?: ($task->created_at ?: now());
            $end   = $task->due_date       ?: $start->copy()->addDay();
            $progress = $task->progress;

            // Distinguish template vs instance vs recurring in the label
            if ($task->is_template || $task->is_autoprogrammable) {
                $label = ($task->is_autoprogrammable ? '🔄 ' : '📋 ') . $task->title;
            } elseif ($task->assignedUser) {
                $label = '👤 ' . ($task->assignedUser->short_name ?: $task->assignedUser->name) . ': ' . $task->title;
            } else {
                $label = $task->title;
            }

            if ($task->parent_id) $label = '   ↳ ' . $label;

            $typeClass = $task->is_template ? 'gantt-master' : ($task->parent_id ? 'gantt-instance' : 'gantt-plain');
            // Only templates should be forced readonly for regular members in this context
            $isReadonly = $task->is_template && auth()->user()->cannot('update', $task);
            $readonlyClass = $isReadonly ? 'gantt-readonly' : '';
            $colorClass = $task->getGanttColorClass();

            return [
                'id'           => (string) $task->id,
                'name'         => $label,
                'start'        => $start->format('Y-m-d'),
                'end'          => $end->format('Y-m-d'),
                'progress'     => $progress,
                'dependencies' => $task->metadata['dependency_id'] ?? ($task->parent_id ? (string) $task->parent_id : ''),
                'custom_class' => "{$typeClass} {$colorClass} {$readonlyClass}",
                'status'       => $task->status,
                'status_label' => __("tasks.statuses.{$task->status}"),
                'priority'     => $task->priority,
                'priority_label' => __("tasks.priorities.{$task->priority}"),
                'urgency'      => $task->urgency,
                'is_template'  => $task->is_template,
                'has_children' => $task->children->count() > 0,
                'assigned_to'  => $task->assignedUser?->name ?? $task->creator?->name,
                'user_name'    => $task->assignedUser?->name ?? ($task->creator?->name ?? 'Sin asignar'),
                'user_initials' => ($task->assignedUser ?: $task->creator) 
                                    ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(($task->assignedUser ?: $task->creator)->name, 0, 2)) 
                                    : '??',
                'user_id'      => $task->assigned_user_id ?? $task->created_by_id,
                'weight'       => $task->cognitive_load ?? 1,
                'parent_id'    => $task->parent_id,
                'parent_title' => $task->parent?->title,
                'readonly'     => auth()->user()->cannot('update', $task),
                'skills'       => $task->skills->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray(),
            ];
        });

        return response()->json($formattedTasks);
    }

    /**
     * Shared logic to get the filtered task set for Gantt
     */
    private function getTaskSet(Request $request, Team $team)
    {
        $user      = auth()->user();
        $isManager = $team->isManager($user);

        // Step 1: Base operational set (visibility)
        $baseTasks = $team->tasks()
            ->with(['parent', 'children', 'assignedUser', 'creator', 'skills'])
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team)
            ->get();

        // Step 2: Expansion rules
        $ganttTaskIds = collect();
        $showCompleted = !session('hide_completed_tasks', true) || $request->status;

        foreach ($baseTasks as $task) {
            $isTaskCompleted = in_array($task->status, ['completed', 'cancelled']);

            // Only add the task itself if it's not completed OR we are showing completed tasks
            if ($showCompleted || !$isTaskCompleted) {
                $ganttTaskIds->push($task->id);
            }

            if ($task->is_template && $team->isCoordinator($user)) {
                // For coordinators, we push children EXCEPT their own (to avoid redundancy with the master bar)
                $task->children->each(function ($child) use ($ganttTaskIds, $showCompleted, $user) {
                    if ($showCompleted || !in_array($child->status, ['completed', 'cancelled'])) {
                        // Skip my own instance if I'm already seeing the master
                        if ($child->assigned_user_id !== $user->id) {
                            $ganttTaskIds->push($child->id);
                        }
                    }
                });
            } elseif ($task->isInstance()) {
                // REDUNDANCY RULE: Only pull the parent if we are NOT the assignee 
                // Members see their instance but hide the master.
                $isMyAssignedInstance = ($task->assigned_user_id === $user->id);
                
                if (!$isMyAssignedInstance) {
                    if ($showCompleted || !$isTaskCompleted) {
                        $ganttTaskIds->push($task->parent_id);
                    }
                }
            }
        }
    
        if ($team->isModerator($user)) {
             $templateIds = $team->tasks()->where('is_template', true)->pluck('id');
             $ganttTaskIds = $ganttTaskIds->merge($templateIds);
        }

        $uniqueIds = $ganttTaskIds->filter()->unique()->values();

        // Step 3: Filters
        $allGanttTasks = Task::with(['parent', 'assignedUser', 'skills'])
            ->whereIn('id', $uniqueIds)
            ->when(session('hide_completed_tasks', true) && !$request->status, fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->skill_id, function ($q, $skillId) {
                $q->where(function ($sq) use ($skillId) {
                    $sq->where('skill_id', $skillId)
                        ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
                });
            })
            ->when($request->priority, fn($q, $priority) => $q->where('priority', $priority))
            ->when($request->assigned_to, fn($q, $assignedTo) => $q->where('assigned_user_id', $assignedTo))
            ->when($request->type, function ($q, $type) {
                if ($type === 'template') {
                    $q->where('is_template', true);
                } elseif ($type === 'instance') {
                    $q->where('is_template', false)->whereNotNull('parent_id');
                } elseif ($type === 'plain') {
                    $q->where('is_template', false)->whereNull('parent_id');
                }
            })
            ->when($request->time_range && $request->time_range !== 'all', function ($q) use ($request) {
                $months = (int) $request->time_range;
                $q->whereBetween('scheduled_date', [
                    now()->subMonths(1),
                    now()->addMonths($months)
                ]);
            })
            ->when($request->limit && $request->limit !== 'all', function ($q) use ($request) {
                $q->limit((int) $request->limit);
            })
            ->get();

        // Step 4: Sorting (Template/Parent first)
        return $allGanttTasks->sortBy(function ($task) {
            $groupId = $task->parent_id ?? $task->id;
            $isChild = $task->parent_id ? 1 : 0;
            return sprintf('%010d-%d-%010d', $groupId, $isChild, $task->id);
        })->values();
    }
}
