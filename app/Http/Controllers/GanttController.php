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
     * Get tasks data formatted for Frappe Gantt
     */
    public function data(Request $request, Team $team)
    {
        $query = $team->tasks()->with(['parent']);

        // Optional quadrant filtering
        if ($request->has('quadrant') && $request->quadrant != 'all') {
            $q = (int) $request->quadrant;
            $query->where(function($query) use ($q) {
                if ($q === 1) { $query->where('priority', 'high')->where('urgency', 'high'); }
                if ($q === 2) { $query->where('priority', 'high')->where('urgency', 'low'); }
                if ($q === 3) { $query->where('priority', 'low')->where('urgency', 'high'); }
                if ($q === 4) { $query->where('priority', 'low')->where('urgency', 'low'); }
            });
        }

        $tasks = $query->get()->map(function (Task $task) {
            // Frappe Gantt expects: id, name, start, end, progress, dependencies, custom_class
            
            // Fallback for dates if not set
            $start = $task->scheduled_date ?: ($task->created_at ?: now());
            $end = $task->due_date ?: $start->copy()->addDay();

            // Progress estimation based on status
            $progress = match($task->status) {
                'completed' => 100,
                'in_progress' => 50,
                'cancelled' => 0,
                default => 0,
            };

            return [
                'id' => (string) $task->id,
                'name' => $task->title,
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'progress' => $progress,
                'dependencies' => $task->parent_id ? (string) $task->parent_id : "",
                'custom_class' => $task->getGanttColorClass(),
                'status' => $task->status,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
            ];
        });

        return response()->json($tasks);
    }
}
