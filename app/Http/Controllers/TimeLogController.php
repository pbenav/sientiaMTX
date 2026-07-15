<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Task;
use App\Models\TimeLog;
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
        $syncingCth = false;
        $cthResult = null;

        // --- 1. SI TIENE CTH ACTIVADO, LA FUENTE DE LA VERDAD ES CTH ---
        if ($user->sync_with_cth) {
            $syncingCth = true;
            $cthStatus = \App\Jobs\SyncWorkdayWithCth::checkStatus($user);
            $activeLog = $user->activeWorkdayLog();
            
            // Determinar si en CTH está trabajando o no
            $isWorkingCth = $cthStatus['success'] ? $cthStatus['is_working'] : (bool)$activeLog;
            
            // --- BLINDAJE DE INTENCIÓN DEL USUARIO Y ALINEACIÓN CON CTH ---
            if ($activeLog && !$isWorkingCth) {
                // CASO 1: En MTX tenía jornada abierta, pero en CTH ya la cerró (ej. aspa verde aplicada).
                // Al pulsar el botón rojo en MTX, su intención es detener, jamás abrir un nuevo evento en CTH.
                $endTime = !empty($cthStatus['end_time']) ? \Carbon\Carbon::parse($cthStatus['end_time'])->setTimezone(date_default_timezone_get()) : now();
                $activeLog->update(['end_at' => $endTime]);
                $activeTaskLog = $user->activeTaskLog();
                if ($activeTaskLog) {
                    $activeTaskLog->update(['end_at' => $endTime]);
                }
                return response()->json([
                    'status' => 'stopped',
                    'message' => __('Workday stopped successfully (Sincronizado con cierre previo en CTH).'),
                    'syncing_cth' => true,
                    'cth_result' => ['success' => true, 'status' => 'stopped']
                ]);
            } elseif (!$activeLog && $isWorkingCth) {
                // CASO 2: En MTX tenía jornada cerrada, pero en CTH ya la inició.
                // Al pulsar el botón verde en MTX, su intención es iniciar, jamás detener el evento de CTH.
                $startTime = !empty($cthStatus['start_time']) ? \Carbon\Carbon::parse($cthStatus['start_time'])->setTimezone(date_default_timezone_get()) : now();
                $user->timeLogs()->create([
                    'type' => 'workday',
                    'start_at' => $startTime,
                ]);
                return response()->json([
                    'status' => 'started',
                    'message' => __('Workday started successfully (Sincronizado con turno activo en CTH).'),
                    'syncing_cth' => true,
                    'cth_result' => ['success' => true, 'status' => 'started']
                ]);
            }
            
            $actionToSend = $activeLog ? 'stop' : 'start';
            
            // Disparar la acción a CTH
            $cthResult = \App\Jobs\SyncWorkdayWithCth::syncNow($user, $actionToSend);

            if (!$cthResult['success']) {
                // Si falla en CTH (ej. medida de gracia requerida o error), NO tocamos el contador local y devolvemos el error
                return response()->json([
                    'status' => $activeLog ? 'started' : 'stopped',
                    'message' => $cthResult['message'],
                    'syncing_cth' => true,
                    'cth_result' => $cthResult
                ]);
            }

            // Si CTH triunfa, alineamos MTX al nuevo estado de CTH
            if ($actionToSend === 'stop') {
                if ($activeLog) {
                    $activeLog->update(['end_at' => now()]);
                }
                $activeTaskLog = $user->activeTaskLog();
                if ($activeTaskLog) {
                    $activeTaskLog->update(['end_at' => now()]);
                }
                return response()->json([
                    'status' => 'stopped',
                    'message' => __('Workday stopped successfully.'),
                    'syncing_cth' => true,
                    'cth_result' => $cthResult
                ]);
            } else {
                if (!$activeLog) {
                    $user->timeLogs()->create([
                        'type' => 'workday',
                        'start_at' => now(),
                    ]);
                }
                return response()->json([
                    'status' => 'started',
                    'message' => __('Workday started successfully.'),
                    'syncing_cth' => true,
                    'cth_result' => $cthResult
                ]);
            }
        }

        // --- 2. FLUJO NORMAL SIN CTH ---
        $activeLog = $user->activeWorkdayLog();
        if ($activeLog) {
            $activeLog->update(['end_at' => now()]);
            
            $activeTaskLog = $user->activeTaskLog();
            if ($activeTaskLog) {
                $activeTaskLog->update(['end_at' => now()]);
            }

            return response()->json([
                'status' => 'stopped',
                'message' => __('Workday stopped successfully.'),
                'syncing_cth' => false,
                'cth_result' => null
            ]);
        }

        $user->timeLogs()->create([
            'type' => 'workday',
            'start_at' => now(),
        ]);

        return response()->json([
            'status' => 'started',
            'message' => __('Workday started successfully.'),
            'syncing_cth' => false,
            'cth_result' => null
        ]);
    }

    /**
     * Start/Stop Task log.
     */
    public function toggleTask(Request $request, $id)
    {
        $task = Activity::find($id) ?? Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => __('Tarea no encontrada.')], 404);
        }
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
            if (method_exists($task, 'syncKanbanColumn')) {
                $task->syncKanbanColumn();
            }
            
            // Sync parent if exists (Recursive up)
            $current = $task;
            while ($current->parent_id) {
                $parent = $current->parent;
                if ($parent && !in_array($parent->status, ['completed', 'cancelled', 'in_progress'])) {
                    $parent->update(['status' => 'in_progress']);
                    if (method_exists($parent, 'syncKanbanColumn')) {
                        $parent->syncKanbanColumn();
                    }
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
        
        $cthStatus = null;
        // --- SINCRONIZACIÓN EN TIEMPO REAL CON CTH ---
        if ($user->sync_with_cth) {
            $cthStatus = \App\Jobs\SyncWorkdayWithCth::checkStatus($user);
            if ($cthStatus['success']) {
                $activeWorkday = $user->activeWorkdayLog();
                if ($cthStatus['is_working'] && !$activeWorkday) {
                    if (request()->has('init')) {
                        // Si en CTH está trabajando pero en MTX no, abrimos jornada en MTX con la hora real de CTH (solo al inicio/login)
                        $startTime = $cthStatus['start_time'] ? \Carbon\Carbon::parse($cthStatus['start_time'])->setTimezone(date_default_timezone_get()) : now();
                        $user->timeLogs()->create([
                            'type' => 'workday',
                            'start_at' => $startTime,
                        ]);
                    }
                } elseif (!$cthStatus['is_working'] && $activeWorkday) {
                    // Si en CTH NO está trabajando pero en MTX sí, cerramos el contador local con la hora EXACTA de CTH
                    $endTime = !empty($cthStatus['end_time']) ? \Carbon\Carbon::parse($cthStatus['end_time'])->setTimezone(date_default_timezone_get()) : now();
                    $activeWorkday->update(['end_at' => $endTime]);
                    
                    // Asegurar que cualquier tarea activa también se cierre
                    $activeTask = $user->activeTaskLog();
                    if ($activeTask) {
                        $activeTask->update(['end_at' => $endTime]);
                    }
                } elseif ($cthStatus['is_working'] && $activeWorkday && !empty($cthStatus['start_time'])) {
                    // Y si en CTH le han cambiado la hora de inicio (start_at), ¡también sincronizamos la hora de inicio en MTX!
                    $cthStartTime = \Carbon\Carbon::parse($cthStatus['start_time'])->setTimezone(date_default_timezone_get());
                    if ($cthStartTime->ne(\Carbon\Carbon::parse($activeWorkday->start_at))) {
                        $activeWorkday->update(['start_at' => $cthStartTime]);
                    }
                }
            }
        }

        $activeTaskLog = $user->activeTaskLog();
        $taskObj = $activeTaskLog ? (Activity::find($activeTaskLog->task_id) ?? Task::find($activeTaskLog->task_id)) : null;

        return response()->json([
            'is_working' => (bool)$user->activeWorkdayLog(),
            'active_task_id' => $activeTaskLog?->task_id,
            'active_task_title' => $taskObj?->title,
            'active_task_team_id' => $taskObj?->team_id,
            'workday_elapsed' => $user->activeWorkdayLog() ? $user->activeWorkdayLog()->start_at->diffInSeconds(now()) : 0,
            'task_elapsed' => $activeTaskLog ? $activeTaskLog->start_at->diffInSeconds(now()) : 0,
            'cth' => $user->sync_with_cth ? [
                'enabled' => true,
                'server' => parse_url($user->cth_api_url ?: config('services.cth.url'), PHP_URL_HOST),
                'user_code' => $user->cth_user_code,
                'work_center_code' => $user->cth_work_center_code,
                'status' => $cthStatus
            ] : ['enabled' => false]
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
        $tasks = $team->activities()->whereHas('timeLogs', function($q) use ($user) {
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

    /**
     * Aplica la medida de gracia de cierre en CTH a través de S2S.
     */
    public function applyCthGraceClosing(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->sync_with_cth) {
            return response()->json(['success' => false, 'message' => 'Sincronización CTH desactivada.']);
        }

        $result = \App\Jobs\SyncWorkdayWithCth::syncNow($user, 'grace_closing');
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
