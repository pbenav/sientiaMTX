<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


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
        $syncingCth = false;

        if ($activeLog) {
            $activeLog->update(['end_at' => now()]);
            
            // Auto-stop active task if workday ends
            $activeTaskLog = $user->activeTaskLog();
            if ($activeTaskLog) {
                $activeTaskLog->update(['end_at' => now()]);
            }

            // Sync with CTH
            if ($user->sync_with_cth) {
                \App\Jobs\SyncWorkdayWithCth::dispatch($user, 'stop');
                $syncingCth = true;
            }

            return response()->json([
                'status' => 'stopped',
                'message' => __('Workday stopped successfully.'),
                'syncing_cth' => $syncingCth
            ]);
        }

        $user->timeLogs()->create([
            'type' => 'workday',
            'start_at' => now(),
        ]);

        // Sync with CTH
        if ($user->sync_with_cth) {
            \App\Jobs\SyncWorkdayWithCth::dispatch($user, 'start');
            $syncingCth = true;
        }

        return response()->json([
            'status' => 'started',
            'message' => __('Workday started successfully.'),
            'syncing_cth' => $syncingCth
        ]);
    }

    /**
     * Start/Stop Task log.
     */
    public function toggleTask(Request $request, Task $task)
    {
        $user = auth()->user();
        if ($user->cannot('view', $task)) {
            return response()->json(['success' => false, 'message' => __('No tienes permiso para interactuar con esta tarea.')], 403);
        }
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
            'active_task_team_id' => $activeTaskLog?->task?->team_id,
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

        // Get my recent workdays grouped by day
        $workdayLogs = $user->timeLogs()
            ->where('type', 'workday')
            ->orderBy('start_at', 'desc')
            ->get()
            ->groupBy(function($log) {
                return $log->start_at->format('Y-m-d');
            })
            ->map(function($logs, $date) {
                $totalMinutes = 0;
                $isActive = false;
                foreach($logs as $log) {
                    if (!$log->end_at) {
                        $isActive = true;
                        $totalMinutes += floor($log->start_at->diffInMinutes(now()));
                    } else {
                        $totalMinutes += floor($log->start_at->diffInMinutes($log->end_at));
                    }
                }
                return (object)[
                    'date' => \Carbon\Carbon::parse($date),
                    'total_minutes' => $totalMinutes,
                    'is_active' => $isActive
                ];
            })
            ->take($presenceLimit);
        $team->load(['members.timeLogs' => function($q) {
            $q->whereNull('end_at');
        }]);
        $teamMembers = $team->members;
        
        $heatmapData = $team->members->whereNotNull('location_lat')->map(function($u) {
            return [
                'user_id' => $u->id,
                'photo' => $u->profile_photo_url,
                'lat' => (float)$u->location_lat,
                'lng' => (float)$u->location_lng,
                'count' => max(10, $u->experience_points / 2), // Intensity based on effort (min 10)
                'name' => app(\App\Services\DemoModeService::class)->isActive() ? app(\App\Services\DemoModeService::class)->mask($u->getRawOriginal('name') ?? $u->name, 'name') : $u->name,
                'area' => $u->working_area_name,
                'radius' => (int)($u->impact_radius ?? 10) * 1000, // meters
                'is_working' => clone $u, // Hack to use isWorking correctly
                'is_active' => $u->last_activity_at && $u->last_activity_at->gt(now()->subMinutes(15))
            ];
        })->map(function($data) {
            $u = $data['is_working'];
            $data['is_working'] = $u->last_login_at ? $u->isWorking() : false;
            return $data;
        })->values();
            
        $services = $team->services()
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->with(['reports' => function($q) {
                $q->latest()->limit(5);
            }])->get();

        $incidencePoints = \App\Models\ServiceReport::whereIn('service_id', $services->pluck('id'))
            ->whereIn('type', ['up', 'down'])
            ->where('created_at', '>=', now()->subHours(1))
            ->with(['user', 'service'])
            ->get()
            ->filter(fn($r) => $r->user && $r->user->location_lat)
            ->map(fn($r) => [
                'lat' => (float)$r->user->location_lat,
                'lng' => (float)$r->user->location_lng,
                'type' => $r->type,
                'service' => $r->service->name,
                'user' => app(\App\Services\DemoModeService::class)->isActive() ? app(\App\Services\DemoModeService::class)->mask($r->user->getRawOriginal('name') ?? $r->user->name, 'name') : $r->user->name,
                'time' => $r->created_at->diffForHumans()
            ])->values();

        return view('time-logs.index', compact('team', 'tasks', 'workdayLogs', 'teamMembers', 'heatmapData', 'services', 'incidencePoints'));
    }
}
