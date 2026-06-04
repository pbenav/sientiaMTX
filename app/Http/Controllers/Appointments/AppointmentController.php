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
    public function index(Team $team)
    {
        $user     = auth()->user();
        $settings = $user->appointmentSettings()->where('team_id', $team->id)->first();

        $upcoming = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->upcoming()
            ->with(['service', 'visitor'])
            ->take(10)
            ->get();

        $todayCitas = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->forDate(now()->toDateString())
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->with(['service', 'visitor'])
            ->orderBy('appointment_time')
            ->get();

        $totalThisMonth = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->whereYear('appointment_date', now()->year)
            ->whereMonth('appointment_date', now()->month)
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->count();

        return view('appointments.index', compact('settings', 'upcoming', 'todayCitas', 'totalThisMonth', 'team'));
    }

    /**
     * Lista completa de citas con filtros.
     */
    public function list(Team $team, Request $request)
    {
        $user  = auth()->user();
        $query = Appointment::where('user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->with(['service', 'visitor'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }
        if ($request->filled('date_from')) {
            $query->where('appointment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->paginate(20)->withQueryString();
        $services     = $user->appointmentServices()->where('team_id', $team->id)->active()->get();

        return view('appointments.list', compact('appointments', 'services', 'team'));
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
        return view('appointments.show', compact('appointment', 'team'));
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
            'appointment_date'  => 'sometimes|date|after_or_equal:today',
            'appointment_time'  => 'sometimes|string',
            'status'            => 'sometimes|in:pending,confirmed,cancelled,completed,blocked',
            'member_notes'      => 'nullable|string|max:2000',
            'expediente_id'     => 'nullable|exists:expedientes,id',
            'cancellation_reason' => 'nullable|string|max:500',
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

        if (isset($data['status']) && in_array($data['status'], ['cancelled', 'blocked'])) {
            $data['cancelled_at'] = now();
            $this->deleteGoogleEvent($appointment);
        }

        $appointment->update($data);

        // Actualizar la tarea si existe
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
                $taskData['due_date'] = $appointment->appointment_date;
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

        return back()->with('success', 'Cita actualizada correctamente.');
    }

    /**
     * Cancela y elimina una cita.
     */
    public function destroy(Team $team, Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        if ($appointment->service->team_id !== $team->id) {
            abort(403);
        }

        $appointment->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        $this->deleteGoogleEvent($appointment);

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
    public function forceDestroy(Team $team, Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        if ($appointment->service->team_id !== $team->id) {
            abort(403);
        }

        $this->deleteGoogleEvent($appointment);

        // Si la cita tenía una tarea generada dinámicamente, se podría borrar o desenlazar.
        // Aquí borramos la cita en cascada.
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
}
