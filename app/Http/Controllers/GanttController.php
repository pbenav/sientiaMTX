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
        $user = auth()->user();
        $isCoordinator = $team->isCoordinator($user);

        $query = $team->tasks()->with(['parent']);

        if ($isCoordinator) {
            $query->where(function($q) {
                $q->where('is_template', true)
                  ->orWhereNull('parent_id')
                  ->orWhere('assigned_user_id', auth()->id());
            });
        } else {
            $query->where(function($q) use ($user) {
                $q->where('assigned_user_id', $user->id)
                  ->orWhere(function($subq) {
                      $subq->whereNull('assigned_user_id')
                           ->whereNull('parent_id')
                           ->where('is_template', false);
                  });
            });
        }

        // Optional quadrant filtering
        if ($request->has('quadrant') && $request->quadrant != 'all') {
            $q = (int) $request->quadrant;
            $query->where(function($query) use ($q) {
                $priorityValues = ['high', 'critical'];
                $lowPriorityValues = ['low', 'medium'];
                
                if ($q === 1) { 
                    $query->whereIn('priority', $priorityValues)->whereIn('urgency', $priorityValues); 
                }
                if ($q === 2) { 
                    $query->whereIn('priority', $priorityValues)->whereNotIn('urgency', $priorityValues); 
                }
                if ($q === 3) { 
                    $query->whereNotIn('priority', $priorityValues)->whereIn('urgency', $priorityValues); 
                }
                if ($q === 4) { 
                    $query->whereNotIn('priority', $priorityValues)->whereNotIn('urgency', $priorityValues); 
                }
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
