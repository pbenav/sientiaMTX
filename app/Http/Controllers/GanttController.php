<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;

class GanttController extends Controller
{
    /**
     * Show the Gantt chart view for a team
     */
    public function index(Team $team)
    {
        $members = $team->members()->get();
        return view('teams.gantt', compact('team', 'members'));
    }

    /**
     * Get tasks data formatted for Frappe Gantt.
     *
     * Visibility rules:
     *  - Owner of a template: sees the template + all child instances (with progress per member)
     *  - Assigned member (instance): sees their instance + all sibling instances of the same parent
     *  - Plain task (no parent/children): appears normally
     */
    public function data(Request $request, Team $team)
    {
        $user      = auth()->user();
        $isManager = $team->isManager($user);

        // Step 1: Get the "operational" base set (same visibility as list/matrix)
        $baseTasks = $team->tasks()
            ->with(['parent', 'children', 'assignedUser'])
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team)
            ->get();

        // Step 2: Expand the task set for Gantt-specific rules
        $ganttTaskIds = collect();

        foreach ($baseTasks as $task) {
            $ganttTaskIds->push($task->id);

            // Expansion rules
            if ($task->is_template && $team->isCoordinator($user)) {
                // Coordinators see all child instances for progress monitoring
                $task->children->each(fn ($child) => $ganttTaskIds->push($child->id));
            } elseif ($task->isInstance()) {
                // Assigned member (including moderator if assigned): see only their context
                if ($task->assigned_user_id === $user->id) {
                    $ganttTaskIds->push($task->parent_id);
                }
            }
        }
    
        // Special case for Moderator (Supervisor): make sure they see templates even if not owners
        if ($team->isModerator($user)) {
             $templateIds = $team->tasks()->where('is_template', true)->pluck('id');
             $ganttTaskIds = $ganttTaskIds->merge($templateIds);
        }

        $uniqueIds = $ganttTaskIds->filter()->unique()->values();

        $allGanttTasks = Task::with(['parent', 'assignedUser'])
            ->whereIn('id', $uniqueIds)
            ->when(session('hide_completed_tasks', true) && !$request->status, fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
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
                    now()->subMonths(1), // Muestra al menos 1 mes pasado
                    now()->addMonths($months)
                ]);
            })
            ->when($request->limit && $request->limit !== 'all', function ($q) use ($request) {
                $q->limit((int) $request->limit);
            })
            ->get();

        // Step 3: Sort hierarchically (Template/Parent first, then its instances/children)
        $allGanttTasks = $allGanttTasks->sortBy(function ($task) {
            // Group by the parent's ID. If no parent, it's a root task, so use its own ID.
            $groupId = $task->parent_id ?? $task->id;
            // Within the group, parents (is_template = true or parent_id = null) come first.
            // Templates/master tasks get 0, children/instances get 1.
            $isChild = $task->parent_id ? 1 : 0;
            
            // Return a sort key: GroupID_isChild_TaskID
            return sprintf('%010d-%d-%010d', $groupId, $isChild, $task->id);
        })->values();

        // Step 4: Optional quadrant filter (applied after expansion and sorting to keep siblings coherent)
        if ($request->filled('quadrant') && $request->quadrant !== 'all') {
            $q             = (int) $request->quadrant;
            $priorityHigh  = ['high', 'critical'];

            $allGanttTasks = $allGanttTasks->filter(function (Task $task) use ($q, $priorityHigh) {
                $hiPri = in_array($task->priority, $priorityHigh);
                $hiUrg = in_array($task->urgency,  $priorityHigh);

                return match ($q) {
                    1       => $hiPri && $hiUrg,
                    2       => $hiPri && !$hiUrg,
                    3       => !$hiPri && $hiUrg,
                    4       => !$hiPri && !$hiUrg,
                    default => true,
                };
            })->values();
        }

        // Step 4: Map to Frappe Gantt format
        $tasks = $allGanttTasks->map(function (Task $task) {
            $start = $task->scheduled_date ?: ($task->created_at ?: now());
            $end   = $task->due_date       ?: $start->copy()->addDay();

            // Use the model's real progress (aggregate for templates, direct field for instances)
            $progress = $task->progress;

            // Distinguish template vs instance vs recurring in the label
            if ($task->is_template || $task->is_autoprogrammable) {
                $label = ($task->is_autoprogrammable ? '🔄 ' : '📋 ') . $task->title;
            } elseif ($task->assignedUser) {
                $label = '👤 ' . ($task->assignedUser->short_name ?: $task->assignedUser->name) . ': ' . $task->title;
            } else {
                $label = $task->title;
            }

            // Add visual indentation for subtasks
            if ($task->parent_id) {
                $label = '   ↳ ' . $label;
            }

            // Determine custom classes for styling
            $typeClass = $task->is_template ? 'gantt-master' : ($task->parent_id ? 'gantt-instance' : 'gantt-plain');
            $colorClass = $task->getGanttColorClass();

            return [
                'id'           => (string) $task->id,
                'name'         => $label,
                'start'        => $start->format('Y-m-d'),
                'end'          => $end->format('Y-m-d'),
                'progress'     => $progress,
                'dependencies' => $task->metadata['dependency_id'] ?? ($task->parent_id ? (string) $task->parent_id : ''),
                'custom_class' => "{$typeClass} {$colorClass}",
                'status'       => $task->status,
                'priority'     => $task->priority,
                'urgency'      => $task->urgency,
                'is_template'  => $task->is_template,
                'has_children' => $task->children->count() > 0,
                'assigned_to'  => $task->assignedUser?->name,
                'parent_title' => $task->parent?->title,
            ];
        });

        // Step 5: Ensure Today is in range even if no tasks exist currently
        $todayStr = now()->format('Y-m-d');
        $tasks->push([
            'id'           => 'today_marker',
            'name'         => '',
            'start'        => $todayStr,
            'end'          => $todayStr,
            'progress'     => 0,
            'custom_class' => 'gantt-today-marker-task', // We will hide this in CSS
        ]);

        return response()->json($tasks);
    }
}
