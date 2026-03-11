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
        return view('teams.gantt', compact('team'));
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

            if ($task->is_template && $task->created_by_id === $user->id) {
                // Owner of a template: add all child instances so each member's progress is visible
                $task->children->each(fn ($child) => $ganttTaskIds->push($child->id));
            } elseif ($task->isInstance()) {
                // Assigned member: add all sibling instances so the team context is visible
                $siblings = Task::where('parent_id', $task->parent_id)->pluck('id');
                $ganttTaskIds = $ganttTaskIds->merge($siblings);

                // Also include the parent template so Frappe can draw the dependency arrow
                $ganttTaskIds->push($task->parent_id);
            }
        }

        $uniqueIds = $ganttTaskIds->filter()->unique()->values();

        $allGanttTasks = Task::with(['parent', 'assignedUser'])
            ->whereIn('id', $uniqueIds)
            ->get();

        // Step 3: Optional quadrant filter (applied after expansion to keep siblings coherent)
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

            // Distinguish template vs instance in the label
            if ($task->is_template) {
                $label = '📋 ' . $task->title;
            } elseif ($task->assignedUser) {
                $label = '👤 ' . $task->assignedUser->name . ': ' . $task->title;
            } else {
                $label = $task->title;
            }

            return [
                'id'           => (string) $task->id,
                'name'         => $label,
                'start'        => $start->format('Y-m-d'),
                'end'          => $end->format('Y-m-d'),
                'progress'     => $progress,
                'dependencies' => $task->parent_id ? (string) $task->parent_id : '',
                'custom_class' => $task->getGanttColorClass(),
                'status'       => $task->status,
                'priority'     => $task->priority,
                'urgency'      => $task->urgency,
                'is_template'  => $task->is_template,
                'assigned_to'  => $task->assignedUser?->name,
            ];
        });

        return response()->json($tasks);
    }
}
