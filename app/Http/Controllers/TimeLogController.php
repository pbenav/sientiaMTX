<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    /**
     * Start/Stop Workday log.
     */
    public function toggleWorkday(Request $request)
    {
        $user = auth()->user();
        $activeLog = $user->activeWorkdayLog();

        if ($activeLog) {
            $activeLog->update(['end_at' => now()]);
            
            // Auto-stop active task if workday ends
            $activeTaskLog = $user->activeTaskLog();
            if ($activeTaskLog) {
                $activeTaskLog->update(['end_at' => now()]);
            }

            return response()->json([
                'status' => 'stopped',
                'message' => __('Workday stopped successfully.')
            ]);
        }

        $user->timeLogs()->create([
            'type' => 'workday',
            'start_at' => now(),
        ]);

        return response()->json([
            'status' => 'started',
            'message' => __('Workday started successfully.')
        ]);
    }

    /**
     * Start/Stop Task log.
     */
    public function toggleTask(Request $request, Task $task)
    {
        $user = auth()->user();
        $activeLog = $user->activeTaskLog();

        // If clicking on the ALREADY active task -> Stop it
        if ($activeLog && $activeLog->task_id === $task->id) {
            $activeLog->update(['end_at' => now()]);
            return response()->json([
                'status' => 'stopped',
                'message' => __('Task tracking stopped.')
            ]);
        }

        // If clicking on DIFFERENT task -> Stop previous and start new
        if ($activeLog) {
            $activeLog->update(['end_at' => now()]);
        }

        $workdayStarted = false;
        if (!$user->activeWorkdayLog()) {
            $user->timeLogs()->create(['type' => 'workday', 'start_at' => now()]);
            $workdayStarted = true;
        }

        // Update task status to in_progress if it's actionable and not already in progress
        if (!in_array($task->status, ['completed', 'cancelled', 'in_progress'])) {
            $task->update(['status' => 'in_progress']);
            $task->syncKanbanColumn();
            
            // Sync parent if exists (Recursive up)
            $current = $task;
            while ($current->parent_id) {
                $parent = $current->parent;
                if (!in_array($parent->status, ['completed', 'cancelled', 'in_progress'])) {
                    $parent->update(['status' => 'in_progress']);
                    $parent->syncKanbanColumn();
                }
                $current = $parent;
            }
        }

        $user->timeLogs()->create([
            'task_id' => $task->id,
            'type' => 'task',
            'start_at' => now(),
        ]);

        return response()->json([
            'status' => 'started',
            'workday_started' => $workdayStarted,
            'message' => __('Working on: ') . $task->title,
            'new_task_status' => $task->status
        ]);
    }

    /**
     * Get current tracking status.
     */
    public function status()
    {
        $user = auth()->user();
        $activeTaskLog = $user->activeTaskLog();
        return response()->json([
            'is_working' => (bool)$user->activeWorkdayLog(),
            'active_task_id' => $activeTaskLog?->task_id,
            'active_task_title' => $activeTaskLog?->task?->title,
            'workday_elapsed' => $user->activeWorkdayLog() ? $user->activeWorkdayLog()->start_at->diffInSeconds(now()) : 0,
            'task_elapsed' => $activeTaskLog ? $activeTaskLog->start_at->diffInSeconds(now()) : 0,
        ]);
    }

    /**
     * Display time tracking reports.
     */
    public function index(Request $request, \App\Models\Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $user = auth()->user();
        
        $effortLimit = (int) $request->input('effort_limit', 10);
        $presenceLimit = (int) $request->input('presence_limit', 10);

        // Get all my tasks in this team that have time logged
        $tasks = $team->tasks()->whereHas('timeLogs', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['timeLogs' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->limit($effortLimit)
        ->get();

        // Get my recent workdays
        $workdayLogs = $user->timeLogs()
            ->where('type', 'workday')
            ->orderBy('start_at', 'desc')
            ->limit($presenceLimit)
            ->get();
        $teamMembers = $team->members->reject(fn($u) => $u->id === $user->id);
        
        $heatmapData = $team->members->whereNotNull('location_lat')->map(function($u) {
            return [
                'lat' => (float)$u->location_lat,
                'lng' => (float)$u->location_lng,
                'count' => max(10, $u->experience_points / 2), // Intensity based on effort (min 10)
                'name' => $u->name,
                'area' => $u->working_area_name,
                'radius' => (int)($u->impact_radius ?? 10) * 1000 // meters
            ];
        })->values();
            
        return view('time-logs.index', compact('team', 'tasks', 'workdayLogs', 'teamMembers', 'heatmapData'));
    }
}
