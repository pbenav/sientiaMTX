<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentBlock;
use App\Models\AppointmentService;
use App\Models\AppointmentSettings;
use App\Models\Team;
use App\Services\AppointmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentAvailabilityService $availability) {}

    /**
     * Dashboard principal de citas previas del miembro.
     */
    public function index(Team $team, Request $request)
    {
        $user     = auth()->user();
        $settings = $user->appointmentSettings()->where('team_id', $team->id)->first();
        
        $selectedDate = $request->get('date', now()->toDateString());

        $upcoming = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->upcoming()
            ->with(['service', 'visitor'])
            ->take(10)
            ->get();

        $todayCitas = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->forDate($selectedDate)
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->with(['service', 'visitor'])
            ->orderBy('appointment_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $monthAppointments = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->whereYear('appointment_date', now()->year)
            ->whereMonth('appointment_date', now()->month)
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $totalThisMonth = array_sum($monthAppointments);

        // Stats: duración de citas (últimos 30 días)
        $pastAppointments = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->where('appointment_date', '>=', now()->subDays(30)->toDateString())
            ->where('status', 'completed')
            ->with(['activity', 'task'])
            ->get();

        $durations = [];
        foreach ($pastAppointments as $app) {
            $seconds = 0;
            if ($app->activity) {
                $seconds = $app->activity->totalTrackedSeconds();
            } elseif ($app->task) {
                $seconds = $app->task->totalTrackedSeconds();
            }
            if ($seconds > 0) {
                $durations[] = $seconds;
            }
        }

        $moda = 0;
        if (count($durations) > 0) {
            $durationsInMinutes = array_map(fn($d) => (int) floor($d / 60), $durations);
            $counts = array_count_values($durationsInMinutes);
            arsort($counts);
            $moda = array_key_first($counts) * 60;
        }

        $statsDuration = [
            'min' => count($durations) > 0 ? min($durations) : 0,
            'max' => count($durations) > 0 ? max($durations) : 0,
            'avg' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
            'mode' => $moda,
            'count' => count($durations)
        ];

        // Sync appointments shown with Google
        $allShown = $upcoming->merge($todayCitas);
        foreach ($allShown as $app) {
            \App\Jobs\SyncAppointmentWithGoogleJob::dispatch($app);
        }

        return view('appointments.index', compact('settings', 'upcoming', 'todayCitas', 'totalThisMonth', 'monthAppointments', 'team', 'selectedDate', 'statsDuration'));
    }

    /**
     * Lista completa de citas con filtros.
     */
    public function list(Team $team, Request $request)
    {
        $user  = auth()->user();

        if ($request->has('clear')) {
            session()->forget("appointments_filters_{$team->id}");
            return redirect()->route('appointments.list', $team);
        }

        // Persistencia de filtros
        $filterKeys = ['status', 'service_id', 'date_from', 'date_to', 'search', 'sort_by', 'sort_dir', 'per_page'];
        
        if (!$request->anyFilled($filterKeys) && !$request->hasAny($filterKeys)) {
            $sessionFilters = session("appointments_filters_{$team->id}", []);
            if (!empty($sessionFilters)) {
                $request->merge($sessionFilters);
            } else {
                $request->merge([
                    'date_from' => now()->toDateString(),
                    'date_to'   => now()->toDateString(),
                    'sort_by'   => 'appointment_date',
                    'sort_dir'  => 'asc',
                ]);
            }
        } else {
            session(["appointments_filters_{$team->id}" => $request->only($filterKeys)]);
        }

        $query = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->with(['service', 'visitor', 'activity', 'task']);

        $sortBy = $request->get('sort_by', 'appointment_date');
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'appointment_date') {
            $query->orderBy('appointments.appointment_date', $sortDir)->orderBy('appointments.appointment_time', $sortDir);
        } elseif ($sortBy === 'created_at' || $sortBy === 'localizador' || $sortBy === 'status') {
            $query->orderBy('appointments.' . $sortBy, $sortDir);
        } elseif ($sortBy === 'visitor') {
            $query->join('appointment_visitors', 'appointments.visitor_id', '=', 'appointment_visitors.id')
                  ->orderBy('appointment_visitors.first_name', $sortDir)
                  ->select('appointments.*');
        } elseif ($sortBy === 'service') {
            $query->join('appointment_services', 'appointments.service_id', '=', 'appointment_services.id')
                  ->orderBy('appointment_services.name', $sortDir)
                  ->select('appointments.*');
        } elseif ($sortBy === 'time') {
            $taskSum = \Illuminate\Support\Facades\DB::table('time_logs')
                ->whereColumn('trackable_id', 'appointments.task_id')
                ->where('trackable_type', \App\Models\Task::class)
                ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)), 0)');

            $activitySum = \Illuminate\Support\Facades\DB::table('time_logs')
                ->whereColumn('trackable_id', 'appointments.activity_id')
                ->where('trackable_type', \App\Models\Activity::class)
                ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)), 0)');

            $query->select('appointments.*')
                  ->selectSub($taskSum, 'task_time')
                  ->selectSub($activitySum, 'activity_time')
                  ->orderByRaw("(COALESCE(task_time, 0) + COALESCE(activity_time, 0)) $sortDir");
        } else {
            $query->orderBy('appointments.appointment_date', 'asc')->orderBy('appointments.appointment_time', 'asc');
        }

        if ($request->filled('status')) {
            $query->where('appointments.status', $request->status);
        }
        if ($request->filled('service_id')) {
            $query->where('appointments.service_id', $request->service_id);
        }
        if ($request->filled('date_from')) {
            $query->where('appointments.appointment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('appointments.appointment_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('appointments.localizador', 'like', "%{$search}%")
                  ->orWhereHas('visitor', function($vq) use ($search) {
                      $vq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('dni', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        if ($perPage > 100) $perPage = 100;

        $appointments = $query->paginate($perPage)->withQueryString();
        $services     = $user->appointmentServices()->where('team_id', $team->id)->active()->get();

        foreach ($appointments as $app) {
            \App\Jobs\SyncAppointmentWithGoogleJob::dispatch($app);
        }

        $statsBaseQuery = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id));

        if ($request->filled('service_id')) {
            $statsBaseQuery->where('service_id', $request->service_id);
        }
        if ($request->filled('date_from')) {
            $statsBaseQuery->where('appointment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsBaseQuery->where('appointment_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $statsBaseQuery->where(function($q) use ($search) {
                $q->where('localizador', 'like', "%{$search}%")
                  ->orWhereHas('visitor', function($vq) use ($search) {
                      $vq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('dni', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $periodStats = (clone $statsBaseQuery)
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Stats: duración de citas (del periodo filtrado)
        $pastAppointments = (clone $statsBaseQuery)
            ->where('status', 'completed')
            ->with(['activity', 'task'])
            ->get();

        $durations = [];
        foreach ($pastAppointments as $app) {
            $seconds = 0;
            if ($app->activity) {
                $seconds = $app->activity->totalTrackedSeconds();
            } elseif ($app->task) {
                $seconds = $app->task->totalTrackedSeconds();
            }
            if ($seconds > 0) {
                $durations[] = $seconds;
            }
        }

        $moda = 0;
        if (count($durations) > 0) {
            $durationsInMinutes = array_map(fn($d) => (int) floor($d / 60), $durations);
            $counts = array_count_values($durationsInMinutes);
            arsort($counts);
            $moda = array_key_first($counts) * 60;
        }

        $statsDuration = [
            'min' => count($durations) > 0 ? min($durations) : 0,
            'max' => count($durations) > 0 ? max($durations) : 0,
            'avg' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
            'mode' => $moda,
            'count' => count($durations)
        ];

        return view('appointments.list', compact('appointments', 'services', 'team', 'periodStats', 'statsDuration'));
    }

    /**
     * Acciones globales para múltiples citas (completar, cancelar, borrar).
     */
    public function bulk(Team $team, Request $request)
    {
        $request->validate([
            'appointment_ids' => 'required|array',
            'appointment_ids.*' => 'exists:appointments,id',
            'bulk_action' => 'required|in:complete,cancel,delete,no_show',
        ]);

        $appointments = Appointment::whereIn('id', $request->appointment_ids)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->get();

        foreach ($appointments as $appointment) {
            $this->authorize('update', $appointment);

            if ($request->bulk_action === 'complete') {
                $appointment->update(['status' => 'completed']);
                if ($appointment->task) {
                    $appointment->task->update(['status' => 'completed', 'progress_percentage' => 100]);
                }
            } elseif ($request->bulk_action === 'cancel' || $request->bulk_action === 'no_show') {
                $reason = $request->bulk_action === 'no_show' 
                    ? 'El ciudadano no se ha presentado a la cita en la fecha y hora acordadas.' 
                    : $request->input('cancellation_reason');

                $appointment->update([
                    'status'              => 'cancelled',
                    'cancelled_at'        => now(),
                    'cancellation_reason' => $reason,
                ]);
                $this->deleteGoogleEvent($appointment);
                $this->deleteGoogleTask($appointment);
                if ($appointment->visitor->email && $appointment->visitor->consent_email) {
                    try {
                        \Mail::to($appointment->visitor->email)
                            ->send(new \App\Mail\AppointmentCancelledMail($appointment));
                    } catch (\Throwable $e) {}
                }
            } elseif ($request->bulk_action === 'delete') {
                $this->deleteGoogleEvent($appointment);
                $this->deleteGoogleTask($appointment);
                if ($appointment->visitor->email && $appointment->visitor->consent_email) {
                    try {
                        \Mail::to($appointment->visitor->email)
                            ->send(new \App\Mail\AppointmentCancelledMail($appointment, $request->input('cancellation_reason') ?: 'Cita anulada y eliminada permanentemente del sistema por la administración.'));
                    } catch (\Throwable $e) {}
                }
                if ($appointment->task) {
                    $appointment->task->delete();
                }
                $appointment->delete();
            }
        }

        return back()->with('success', 'Acción masiva ejecutada correctamente en ' . $appointments->count() . ' citas.');
    }

    /**
     * Muestra/edita una cita concreta.
     */
    public function show(Team $team, Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        if ($appointment->service->team_id !== $team->id) {
            abort(403);
        }
        $appointment->load(['service', 'visitor', 'task', 'expediente']);
        
        \App\Jobs\SyncAppointmentWithGoogleJob::dispatchSync($appointment);
        
        // --- Buscar siguiente cita (Next Appointment) ---
        $user = auth()->user();
        $sessionFilters = session("appointments_filters_{$team->id}", []);
        
        $query = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id));

        // Aplicar filtros de sesión
        if (!empty($sessionFilters['service_id'])) {
            $query->where('service_id', $sessionFilters['service_id']);
        }
        if (!empty($sessionFilters['date_from'])) {
            $query->where('appointment_date', '>=', $sessionFilters['date_from']);
        }
        if (!empty($sessionFilters['date_to'])) {
            $query->where('appointment_date', '<=', $sessionFilters['date_to']);
        }
        if (!empty($sessionFilters['search'])) {
            $search = $sessionFilters['search'];
            $query->where(function($q) use ($search) {
                $q->where('localizador', 'like', "%{$search}%")
                  ->orWhereHas('visitor', function($vq) use ($search) {
                      $vq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('dni', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Saltar las completadas, canceladas o bloqueadas
        $query->whereNotIn('status', ['completed', 'cancelled', 'blocked']);
        
        // Aplicar el mismo orden de la lista
        $sortBy = $sessionFilters['sort_by'] ?? 'appointment_date';
        $sortDir = ($sessionFilters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'appointment_date') {
            $query->orderBy('appointment_date', $sortDir)->orderBy('appointment_time', $sortDir);
        } elseif ($sortBy === 'created_at' || $sortBy === 'localizador' || $sortBy === 'status') {
            $query->orderBy('appointments.' . $sortBy, $sortDir);
        } elseif ($sortBy === 'visitor') {
            $query->join('appointment_visitors', 'appointments.visitor_id', '=', 'appointment_visitors.id')
                  ->orderBy('appointment_visitors.first_name', $sortDir)
                  ->select('appointments.*');
        } elseif ($sortBy === 'service') {
            $query->join('appointment_services', 'appointments.service_id', '=', 'appointment_services.id')
                  ->orderBy('appointment_services.name', $sortDir)
                  ->select('appointments.*');
        } else {
            $query->orderBy('appointment_date', 'asc')->orderBy('appointment_time', 'asc');
        }

        $orderedIds = $query->pluck('appointments.id')->toArray();
        
        $nextAppointment = null;
        $currentIndex = array_search($appointment->id, $orderedIds);
        
        if ($currentIndex !== false && isset($orderedIds[$currentIndex + 1])) {
            $nextAppointment = Appointment::find($orderedIds[$currentIndex + 1]);
        } elseif ($currentIndex === false && count($orderedIds) > 0) {
             if (in_array($sortBy, ['appointment_date', ''])) {
                 $nextAppId = null;
                 $apps = Appointment::whereIn('id', $orderedIds)
                     ->select('id', 'appointment_date', 'appointment_time')
                     ->get()
                     ->keyBy('id');
                     
                 foreach($orderedIds as $id) {
                     $app = $apps[$id] ?? null;
                     if ($app) {
                         if ($app->appointment_date > $appointment->appointment_date || 
                            ($app->appointment_date == $appointment->appointment_date && $app->appointment_time > $appointment->appointment_time)) {
                             $nextAppId = $id;
                             break;
                         }
                     }
                 }
                 if ($nextAppId) {
                     $nextAppointment = Appointment::find($nextAppId);
                 } else {
                     $nextAppointment = Appointment::find($orderedIds[0]);
                 }
             } else {
                 $nextAppointment = Appointment::find($orderedIds[0]);
             }
        }
        
        return view('appointments.show', compact('appointment', 'team', 'nextAppointment'));
    }

    /**
     * Actualiza una cita (mover fecha/hora, notas, expediente).
     */
    public function update(Team $team, Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        if ($appointment->service->team_id !== $team->id) {
            abort(403);
        }

        $data = $request->validate([
            'appointment_date'  => 'sometimes|date',
            'appointment_time'  => 'sometimes|string',
            'status'            => 'sometimes|in:pending,confirmed,cancelled,completed,blocked,no_show',
            'member_notes'      => 'nullable|string|max:2000',
            'expediente_id'     => 'nullable|exists:expedientes,id',
            'cancellation_reason' => 'nullable|string|max:500',
            'visitor_full_name' => 'nullable|string|max:255',
            'visitor_dni'       => 'nullable|string|max:50',
            'visitor_email'     => 'nullable|email|max:255',
            'visitor_phone'     => 'nullable|string|max:50',
            'visitor_city'      => 'nullable|string|max:255',
            'visitor_postal_code' => 'nullable|string|max:50',
            'visitor_observations' => 'nullable|string|max:2000',
            'tracked_time_format' => 'nullable|string|regex:/^\d{1,3}:\d{2}:\d{2}$/',
        ]);

        // Si cambia fecha/hora, revalidar disponibilidad
        if (isset($data['appointment_date']) || isset($data['appointment_time'])) {
            $newDate = Carbon::parse($data['appointment_date'] ?? $appointment->appointment_date);
            $newTime = $data['appointment_time'] ?? $appointment->appointment_time;

            $isOwnSlot = $appointment->appointment_date->eq($newDate) && $appointment->appointment_time === $newTime . ':00';

            if (!$isOwnSlot && !$this->availability->isSlotAvailable($appointment->service, $newDate, $newTime)) {
                return back()->withErrors(['appointment_time' => 'El tramo seleccionado no está disponible.']);
            }
        }

        $originalDate = $appointment->appointment_date;
        $originalTime = $appointment->appointment_time;

        if (isset($data['status']) && in_array($data['status'], ['cancelled', 'blocked'])) {
            $data['cancelled_at'] = now();
            $this->deleteGoogleEvent($appointment);
            $this->deleteGoogleTask($appointment);
        }

        $appointment->update($data);

        if ($request->has('visitor_full_name')) {
            $appointment->visitor->update([
                'full_name'    => $request->input('visitor_full_name'),
                'dni'          => $request->input('visitor_dni'),
                'email'        => $request->input('visitor_email'),
                'phone'        => $request->input('visitor_phone'),
                'city'         => $request->input('visitor_city'),
                'postal_code'  => $request->input('visitor_postal_code'),
                'observations' => $request->input('visitor_observations'),
            ]);
        }

        // Si cambió la fecha o la hora, y el visitante consintió el email, le notificamos
        $dateChanged = isset($data['appointment_date']) && Carbon::parse($data['appointment_date'])->ne($originalDate);
        $timeChanged = isset($data['appointment_time']) && $data['appointment_time'] . ':00' !== $originalTime;

        if (($dateChanged || $timeChanged) && $appointment->visitor->consent_email && $appointment->visitor->email) {
            try {
                \Mail::to($appointment->visitor->email)
                    ->locale(app()->getLocale())
                    ->send(new \App\Mail\AppointmentModifiedMail($appointment));
            } catch (\Throwable $e) {
                \Log::warning("AppointmentModified mail failed: " . $e->getMessage());
            }
        }

        $task = $appointment->task;
        if (!$task && $appointment->localizador) {
            $task = \App\Models\Task::where('title', 'like', "% — {$appointment->localizador}")->first();
            if ($task) {
                $appointment->update(['task_id' => $task->id]);
            }
        }

        if ($task) {
            $taskData = [];
            if (isset($data['appointment_date']) || isset($data['appointment_time'])) {
                $taskData['due_date'] = $appointment->end_datetime;
            }
            if (array_key_exists('expediente_id', $data)) {
                $taskData['expediente_id'] = $data['expediente_id'];
            }
            if (isset($data['status'])) {
                if ($data['status'] === 'completed') {
                    $taskData['status'] = 'completed';
                    $taskData['progress_percentage'] = 100;
                } elseif (in_array($data['status'], ['pending', 'confirmed'])) {
                    if ($task->status === 'completed') {
                        $taskData['status'] = 'in_progress';
                        $taskData['progress_percentage'] = 0;
                    }
                }
            }
            if (!empty($taskData)) {
                $task->update($taskData);
            }
        }

        if ($request->has('tracked_time_format') && ($appointment->activity || $appointment->task)) {
            $taskObj = $appointment->activity ?? $appointment->task;
            $timeFormat = $request->input('tracked_time_format');
            $parts = explode(':', $timeFormat);
            $totalSeconds = 0;
            if (count($parts) === 3) {
                $totalSeconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
            
            // Borrar time logs de tipo task para esta actividad/tarea
            $taskObj->timeLogs()->delete();
            
            if ($totalSeconds > 0) {
                // Crear un único log de la duración deseada
                $start = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time);
                $end = $start->copy()->addSeconds($totalSeconds);
                
                $taskObj->timeLogs()->create([
                    'user_id' => $appointment->user_id ?? auth()->id(),
                    'type' => 'task',
                    'start_at' => $start,
                    'end_at' => $end,
                    'note' => 'Ajuste manual de duración desde edición de cita',
                ]);
            }
        }

        return back()->with('success', 'Cita actualizada correctamente.');
    }

    /**
     * Cancela y elimina una cita.
     */
    public function destroy(Request $request, Team $team, Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        if ($appointment->service->team_id !== $team->id) {
            abort(403);
        }

        $appointment->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $request->input('cancellation_reason'),
        ]);
        $this->deleteGoogleEvent($appointment);
        $this->deleteGoogleTask($appointment);

        // Notificar al visitante si tiene email y consintió
        if ($appointment->visitor->email && $appointment->visitor->consent_email) {
            try {
                \Mail::to($appointment->visitor->email)
                    ->send(new \App\Mail\AppointmentCancelledMail($appointment));
            } catch (\Throwable $e) {
                \Log::warning("AppointmentCancelled mail failed: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Cita cancelada correctamente.');
    }

    /**
     * Elimina físicamente una cita de la base de datos.
     */
    public function forceDestroy(Request $request, Team $team, Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        if ($appointment->service->team_id !== $team->id) {
            abort(403);
        }

        $this->deleteGoogleEvent($appointment);
        $this->deleteGoogleTask($appointment);

        // Notificar al visitante antes de eliminarla físicamente
        if ($appointment->visitor->email && $appointment->visitor->consent_email) {
            try {
                \Mail::to($appointment->visitor->email)
                    ->send(new \App\Mail\AppointmentCancelledMail($appointment, $request->input('cancellation_reason') ?: 'Cita anulada y eliminada permanentemente del sistema por la administración.'));
            } catch (\Throwable $e) {
                \Log::warning("AppointmentCancelled mail failed on forceDestroy: " . $e->getMessage());
            }
        }

        // Eliminar la tarea asociada si existe
        if ($appointment->task) {
            $appointment->task->delete();
        }

        // Eliminar la cita físicamente
        $appointment->delete();

        return redirect()->route('appointments.list', $team)->with('success', 'Cita eliminada definitivamente.');
    }

    /**
     * API: agenda semanal en JSON para la vista de calendario.
     */
    public function agenda(Team $team, Request $request)
    {
        $user  = auth()->user();
        $start = Carbon::parse($request->get('start', now()->startOfWeek()));
        $end   = Carbon::parse($request->get('end', now()->endOfWeek()));

        $appointments = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->with(['service', 'visitor'])
            ->get()
            ->map(fn($a) => [
                'id'           => $a->id,
                'localizador'  => $a->localizador,
                'title'        => $a->service->name . ' — ' . $a->visitor->full_name,
                'date'         => $a->appointment_date->toDateString(),
                'time'         => $a->appointment_time,
                'duration'     => $a->slot_duration_minutes,
                'status'       => $a->status,
                'status_label' => $a->status_label,
                'status_color' => $a->status_color,
            ]);

        $blocks = AppointmentBlock::where('user_id', $user->id)
            ->where(function($q) use ($team) {
                $q->whereNull('service_id')
                  ->orWhereHas('service', fn($sq) => $sq->where('team_id', $team->id));
            })
            ->where('end_datetime', '>=', $start)
            ->where('start_datetime', '<=', $end)
            ->get()
            ->map(fn($b) => [
                'id'     => 'block_' . $b->id,
                'title'  => '🚫 ' . ($b->reason ?? 'Tramo bloqueado'),
                'start'  => $b->start_datetime->toIso8601String(),
                'end'    => $b->end_datetime->toIso8601String(),
                'type'   => 'block',
            ]);

        return response()->json([
            'appointments' => $appointments,
            'blocks'       => $blocks,
        ]);
    }

    private function deleteGoogleEvent(Appointment $appointment): void
    {
        if ($appointment->google_event_id) {
            try {
                $googleService = new \App\Services\GoogleService();
                if ($googleService->setTokenForUser($appointment->member)) {
                    $googleService->deleteEvent($appointment->google_event_id);
                    $appointment->update(['google_event_id' => null]);
                }
            } catch (\Throwable $e) {
                \Log::error("Error eliminando cita en Google Calendar: " . $e->getMessage());
            }
        }
    }

    private function deleteGoogleTask(Appointment $appointment): void
    {
        if ($appointment->google_task_id) {
            try {
                $googleService = new \App\Services\GoogleService();
                if ($googleService->setTokenForUser($appointment->member)) {
                    $googleService->deleteTask('@default', $appointment->google_task_id);
                    $appointment->update(['google_task_id' => null]);
                }
            } catch (\Throwable $e) {
                \Log::error("Error eliminando tarea en Google Tasks: " . $e->getMessage());
            }
        }
    }
}
