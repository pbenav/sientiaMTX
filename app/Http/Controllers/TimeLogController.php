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
        $intent = $request->input('intent'); // 'start' or 'stop'

        // Bloqueo atómico de 3 segundos para prevenir doble clics
        $lock = \Illuminate\Support\Facades\Cache::lock('workday_toggle_' . $user->id, 3);
        if (!$lock->get()) {
            return response()->json(['success' => false, 'message' => __('Petición en curso, por favor espera.')], 429);
        }

        try {
            $activeLog = $user->activeWorkdayLog();

            // SANEAMIENTO CRONOLÓGICO: Si la jornada colgada es de ayer, forzar cierre y reiniciar estado
            if ($activeLog && !$activeLog->start_at->isToday()) {
                $hardCapMinutes = \App\Models\TimeLog::ANOMALY_HARD_CAP_HOURS * 60;
                $expected = \App\Models\TimeLog::expectedMinutesForUser($user, $activeLog->start_at);
                $duration = ($expected > 0 && $expected <= $hardCapMinutes) ? $expected : $hardCapMinutes;
                
                $activeLog->update(['end_at' => $activeLog->start_at->copy()->addMinutes($duration)]);
                $activeLog->fresh()->markAnomalousIfNeeded();
                $activeLog = null; // Reiniciar estado
            }

            // BLINDAJE DE INTENCIÓN FRONTEND
            if ($intent === 'stop' && !$activeLog) {
                return response()->json([
                    'status' => 'stopped',
                    'message' => __('Jornada ya estaba detenida por otro dispositivo.'),
                    'syncing_cth' => $user->sync_with_cth,
                    'cth_result' => ['success' => true, 'status' => 'stopped']
                ]);
            }
            if ($intent === 'start' && $activeLog) {
                return response()->json([
                    'status' => 'started',
                    'message' => __('Jornada ya estaba activa en otro dispositivo.'),
                    'syncing_cth' => $user->sync_with_cth,
                    'cth_result' => ['success' => true, 'status' => 'started']
                ]);
            }

            if ($user->sync_with_cth) {
                $cthStatus = \App\Jobs\SyncWorkdayWithCth::checkStatus($user, true);
                $isWorkingCth = $cthStatus['success'] ? $cthStatus['is_working'] : (bool)$activeLog;
                
                if ($intent === 'start' && $isWorkingCth) {
                    $startTime = !empty($cthStatus['start_time']) ? \Carbon\Carbon::parse($cthStatus['start_time'])->setTimezone(date_default_timezone_get()) : now();
                    $user->timeLogs()->create(['type' => 'workday', 'start_at' => $startTime]);
                    return response()->json([
                        'status' => 'started',
                        'message' => __('Sincronizado con turno activo en CTH.'),
                        'syncing_cth' => true,
                        'cth_result' => ['success' => true, 'status' => 'started']
                    ]);
                }
                
                if ($intent === 'stop' && !$isWorkingCth) {
                    $endTime = !empty($cthStatus['end_time']) ? \Carbon\Carbon::parse($cthStatus['end_time'])->setTimezone(date_default_timezone_get()) : now();
                    $workdayEndTime = $endTime->copy();
                    if ($workdayEndTime->lt($activeLog->start_at)) $workdayEndTime = $activeLog->start_at;
                    $activeLog->update(['end_at' => $workdayEndTime]);
                    $activeLog->fresh()->markAnomalousIfNeeded();
                    
                    if ($activeTaskLog = $user->activeTaskLog()) {
                        $taskEndTime = $endTime->copy();
                        if ($taskEndTime->lt($activeTaskLog->start_at)) $taskEndTime = $activeTaskLog->start_at;
                        $activeTaskLog->update(['end_at' => $taskEndTime]);
                    }
                    return response()->json([
                        'status' => 'stopped',
                        'message' => __('Sincronizado con cierre previo en CTH.'),
                        'syncing_cth' => true,
                        'cth_result' => ['success' => true, 'status' => 'stopped']
                    ]);
                }
                
                $actionToSend = $intent ?: ($activeLog ? 'stop' : 'start');
                $cthResult = \App\Jobs\SyncWorkdayWithCth::syncNow($user, $actionToSend);

                if (!$cthResult['success']) {
                    return response()->json([
                        'status' => $activeLog ? 'started' : 'stopped',
                        'message' => $cthResult['message'],
                        'syncing_cth' => true,
                        'cth_result' => $cthResult
                    ]);
                }

                if ($actionToSend === 'stop') {
                    if ($activeLog) {
                        $cthStartTime = !empty($cthResult['start_time']) ? \Carbon\Carbon::parse($cthResult['start_time'])->setTimezone(date_default_timezone_get()) : null;
                        $cthEndTime = !empty($cthResult['end_time']) ? \Carbon\Carbon::parse($cthResult['end_time'])->setTimezone(date_default_timezone_get()) : now();
                        
                        if ($cthStartTime && abs($cthStartTime->diffInMinutes($activeLog->start_at)) < 15) {
                            $activeLog->update(['start_at' => $cthStartTime, 'end_at' => max($cthStartTime, $cthEndTime)]);
                        } else {
                            $activeLog->update(['end_at' => now()]);
                        }
                        $activeLog->fresh()->markAnomalousIfNeeded();
                    }
                    if ($activeTaskLog = $user->activeTaskLog()) {
                        $activeTaskLog->update(['end_at' => now()]);
                    }
                    return response()->json(['status' => 'stopped', 'message' => __('Jornada detenida en CTH.'), 'syncing_cth' => true, 'cth_result' => $cthResult]);
                } else {
                    if (!$activeLog) $user->timeLogs()->create(['type' => 'workday', 'start_at' => now()]);
                    return response()->json(['status' => 'started', 'message' => __('Jornada iniciada en CTH.'), 'syncing_cth' => true, 'cth_result' => $cthResult]);
                }
            }

            // SIN CTH
            $actionToPerform = $intent ?: ($activeLog ? 'stop' : 'start');
            if ($actionToPerform === 'stop') {
                if ($activeLog) {
                    $activeLog->update(['end_at' => now()]);
                    $activeLog->fresh()->markAnomalousIfNeeded();
                    if ($activeTaskLog = $user->activeTaskLog()) $activeTaskLog->update(['end_at' => now()]);
                }
                return response()->json(['status' => 'stopped', 'message' => __('Workday stopped successfully.'), 'syncing_cth' => false, 'cth_result' => null]);
            }

            if (!$activeLog) $user->timeLogs()->create(['type' => 'workday', 'start_at' => now()]);
            return response()->json(['status' => 'started', 'message' => __('Workday started successfully.'), 'syncing_cth' => false, 'cth_result' => null]);

        } finally {
            $lock->release();
        }
    }
    public function toggleTask(Request $request, $id)
    {
        $task = \App\Models\Activity::find($id) ?? \App\Models\Task::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => __('Tarea no encontrada.')], 404);
        }
        $user = auth()->user();
        if ($user->cannot('view', $task)) {
            return response()->json(['success' => false, 'message' => __('No tienes permiso para interactuar con esta tarea.')], 403);
        }

        $intent = $request->input('intent'); // 'start' or 'stop'
        $lock = \Illuminate\Support\Facades\Cache::lock('task_toggle_' . $user->id, 3);
        if (!$lock->get()) {
            return response()->json(['success' => false, 'message' => __('Petición en curso, por favor espera.')], 429);
        }

        try {
            $activeLogs = $user->timeLogs()->where('type', 'task')->whereNull('end_at')->get();
            $isSameTask = $activeLogs->contains('task_id', $task->id);

            // BLINDAJE DE INTENCIÓN FRONTEND
            if ($intent === 'stop' && !$isSameTask) {
                return response()->json([
                    'status' => 'stopped',
                    'message' => __('La tarea ya estaba detenida.'),
                    'total_human_time' => method_exists($task, 'totalTrackedTimeHuman') ? $task->fresh()->totalTrackedTimeHuman() : '0m'
                ]);
            }
            if ($intent === 'start' && $isSameTask) {
                return response()->json([
                    'status' => 'started',
                    'message' => __('La tarea ya estaba activa.'),
                    'total_human_time' => method_exists($task, 'totalTrackedTimeHuman') ? $task->fresh()->totalTrackedTimeHuman() : '0m'
                ]);
            }

            // DETENER TODAS LAS TAREAS PREVIAS (Exclusividad)
            if ($activeLogs->isNotEmpty()) {
                $user->timeLogs()->where('type', 'task')->whereNull('end_at')->update(['end_at' => now()]);
            }

            $actionToPerform = $intent ?: ($isSameTask ? 'stop' : 'start');

            if ($actionToPerform === 'stop') {
                return response()->json([
                    'status' => 'stopped',
                    'message' => __('Task tracking stopped.'),
                    'total_human_time' => method_exists($task, 'totalTrackedTimeHuman') ? $task->fresh()->totalTrackedTimeHuman() : '0m'
                ]);
            }

            // START ACTION
            $workdayStarted = false;
            if (!$user->activeWorkdayLog()) {
                if ($user->sync_with_cth) {
                    $cthResult = \App\Jobs\SyncWorkdayWithCth::syncNow($user, 'start');
                    if (!$cthResult['success']) {
                        return response()->json(['success' => false, 'message' => $cthResult['message']]);
                    }
                }
                $user->timeLogs()->create(['type' => 'workday', 'start_at' => now()]);
                $workdayStarted = true;
            }

            $user->timeLogs()->create([
                'task_id' => $task->id,
                'type' => 'task',
                'start_at' => now(),
                'metadata' => ['team_id' => $task->team_id ?? null]
            ]);

            return response()->json([
                'status' => 'started',
                'message' => __('Task tracking started.'),
                'workday_started' => $workdayStarted,
                'total_human_time' => method_exists($task, 'totalTrackedTimeHuman') ? $task->fresh()->totalTrackedTimeHuman() : '0m'
            ]);

        } finally {
            $lock->release();
        }
    }
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
                    // Si en CTH NO está trabajando pero en MTX sí, cerramos el contador local.
                    $cthStartTime = !empty($cthStatus['start_time']) ? \Carbon\Carbon::parse($cthStatus['start_time'])->setTimezone(date_default_timezone_get()) : null;
                    $endTime = !empty($cthStatus['end_time']) ? \Carbon\Carbon::parse($cthStatus['end_time'])->setTimezone(date_default_timezone_get()) : now();
                    
                    if ($cthStartTime && abs($cthStartTime->diffInMinutes($activeWorkday->start_at)) < 15) {
                        // Coinciden en su inicio: sincronizamos ambos valores con CTH
                        $activeWorkday->update([
                            'start_at' => $cthStartTime,
                            'end_at' => max($cthStartTime, $endTime)
                        ]);
                    } else {
                        // Tramo diferente o sin contexto CTH: cerramos el actual limpiamente
                        $activeWorkday->update(['end_at' => now()]);
                    }
                    
                    // Asegurar que cualquier tarea activa también se cierre
                    $activeTask = $user->activeTaskLog();
                    if ($activeTask) {
                        $taskEndTime = $endTime->copy();
                        if ($taskEndTime->lt($activeTask->start_at)) $taskEndTime = $activeTask->start_at;
                        $activeTask->update(['end_at' => $taskEndTime]);
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
            'workday_elapsed' => $user->activeWorkdayLog() ? max(0, $user->activeWorkdayLog()->start_at->diffInSeconds(now(), false)) : 0,
            'task_elapsed' => $activeTaskLog ? (max(0, $activeTaskLog->start_at->diffInSeconds(now(), false)) + ($taskObj ? $taskObj->totalTrackedSeconds() : 0)) : 0,
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

        // Get all my tasks in this team that have time logged and are visible to me
        $tasks = $team->activities()
            ->visibleTo($user, $team->isManager($user))
            ->where(function($q) use ($user) {
                $q->whereNull('metadata->is_distributed_instance')
                  ->orWhere('metadata->is_distributed_instance', false)
                  ->orWhere('metadata->is_distributed_instance', 'false')
                  ->orWhere('metadata->distributed_user_id', $user->id);
            })
            ->whereHas('timeLogs', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with([
                'timeLogs' => function($q) use ($user) {
                    $q->where('user_id', $user->id)->orderByDesc('start_at');
                },
                'parent',
                'skill'
            ])
            ->orderByDesc(
                \App\Models\TimeLog::select('start_at')
                    ->whereColumn('task_id', 'activities.id')
                    ->where('user_id', $user->id)
                    ->orderByDesc('start_at')
                    ->limit(1)
            )
            ->limit($effortLimit)
            ->get();

        // Get my recent workdays grouped by day
        // Uses effectiveMinutes() so anomalous logs (jornada olvidada abierta) are normalized
        // to the user's configured schedule instead of inflating the stats.
        $workdayLogs = $user->timeLogs()
            ->where('type', 'workday')
            ->orderBy('start_at', 'desc')
            ->get()
            ->groupBy(function($log) {
                return $log->start_at->format('Y-m-d');
            })
            ->map(function($logs, $date) {
                $totalMinutes = 0;
                $isActive     = false;
                $hasAnomaly   = false;
                $anomalyCount = 0;

                foreach($logs as $log) {
                    if (!$log->end_at) {
                        $isActive      = true;
                        $totalMinutes += (int) $log->start_at->diffInMinutes(now());
                    } else {
                        $totalMinutes += $log->effectiveMinutes();
                        if ($log->is_anomalous) {
                            $hasAnomaly = true;
                            $anomalyCount++;
                        }
                    }
                }

                // Red de seguridad a nivel de DÍA: si la suma de todos los registros del día
                // supera el máximo permitido (ya sea por turnos duplicados, errores de API, etc),
                // capamos el día entero.
                $firstLog = $logs->first();
                $expectedForDay = \App\Models\TimeLog::expectedMinutesForUser($firstLog->user ?? $user, \Carbon\Carbon::parse($date));
                $hardCapMinutes = \App\Models\TimeLog::ANOMALY_HARD_CAP_HOURS * 60;
                $dailyThreshold = min((int) ($expectedForDay * 1.20), $hardCapMinutes);

                if ($totalMinutes > $dailyThreshold) {
                    $hasAnomaly = true;
                    // Si superaba el threshold, normalizamos al expected
                    $totalMinutes = $expectedForDay > 0 && $expectedForDay <= $hardCapMinutes ? $expectedForDay : $hardCapMinutes;
                }

                // Última red de seguridad: jamás superar el hard cap de 10h en un día
                if ($totalMinutes > $hardCapMinutes) {
                    $totalMinutes = $hardCapMinutes;
                }

                return (object)[
                    'date'          => \Carbon\Carbon::parse($date),
                    'total_minutes' => $totalMinutes,
                    'is_active'     => $isActive,
                    'has_anomaly'   => $hasAnomaly,
                    'anomaly_count' => $anomalyCount ?: ($hasAnomaly ? 1 : 0),
                ];
            })
            ->take($presenceLimit);
        $team->load(['members.timeLogs' => function($q) {
            $q->whereNull('end_at');
        }, 'members.badges']);
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

        // El usuario quiere que MTX refleje la hora REAL (ej. 19:36) en lugar de la hora programada que impondrá CTH (ej. 19:00).
        // Cerramos los contadores locales de MTX con la hora exacta actual antes de pedirle a CTH que haga el cierre de gracia.
        $activeLog = $user->activeWorkdayLog();
        if ($activeLog) {
            $activeLog->update(['end_at' => now()]);
            $activeLog->fresh()->markAnomalousIfNeeded();
        }
        $activeTaskLog = $user->activeTaskLog();
        if ($activeTaskLog) {
            $activeTaskLog->update(['end_at' => now()]);
        }

        $result = \App\Jobs\SyncWorkdayWithCth::syncNow($user, 'grace_closing');
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
