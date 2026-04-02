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

        // Ensure user has an active workday to track tasks (Optional, depends on business rule)
        // If not, we could auto-start workday here.
        if (!$user->activeWorkdayLog()) {
             $user->timeLogs()->create(['type' => 'workday', 'start_at' => now()]);
        }

        $user->timeLogs()->create([
            'task_id' => $task->id,
            'type' => 'task',
            'start_at' => now(),
        ]);

        return response()->json([
            'status' => 'started',
            'message' => __('Working on: ') . $task->title
        ]);
    }

    /**
     * Get current tracking status.
     */
    public function status()
    {
        $user = auth()->user();
        return response()->json([
            'is_working' => (bool)$user->activeWorkdayLog(),
            'active_task_id' => $user->activeTaskLog()?->task_id,
            'workday_elapsed' => $user->activeWorkdayLog() ? $user->activeWorkdayLog()->start_at->diffInSeconds(now()) : 0,
            'task_elapsed' => $user->activeTaskLog() ? $user->activeTaskLog()->start_at->diffInSeconds(now()) : 0,
        ]);
    }

    /**
     * Display time tracking reports.
     */
    public function index(Request $request, \App\Models\Team $team)
    {
        $user = auth()->user();
        
        // Get all my tasks in this team that have time logged
        $tasks = $team->tasks()->whereHas('timeLogs', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['timeLogs' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }])->get();

        // Get my recent workdays
        $workdayLogs = $user->timeLogs()
            ->where('type', 'workday')
            ->orderBy('start_at', 'desc')
            ->limit(30)
            ->get();
            
        return view('time-logs.index', compact('team', 'tasks', 'workdayLogs'));
    }
}
