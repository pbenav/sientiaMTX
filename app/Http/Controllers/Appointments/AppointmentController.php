<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentBlock;
use App\Models\AppointmentSchedule;
use App\Models\AppointmentService;
use App\Models\AppointmentSettings;
use App\Models\Expediente;
use App\Services\AppointmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentAvailabilityService $availability) {}

    /**
     * Dashboard principal de citas previas del miembro.
     */
    public function index()
    {
        $user     = auth()->user();
        $settings = $user->appointmentSettings;

        $upcoming = Appointment::where('user_id', $user->id)
            ->upcoming()
            ->with(['service', 'visitor'])
            ->take(10)
            ->get();

        $todayCitas = Appointment::where('user_id', $user->id)
            ->forDate(now()->toDateString())
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->with(['service', 'visitor'])
            ->orderBy('appointment_time')
            ->get();

        $totalThisMonth = Appointment::where('user_id', $user->id)
            ->whereYear('appointment_date', now()->year)
            ->whereMonth('appointment_date', now()->month)
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->count();

        return view('appointments.index', compact('settings', 'upcoming', 'todayCitas', 'totalThisMonth'));
    }

    /**
     * Lista completa de citas con filtros.
     */
    public function list(Request $request)
    {
        $user  = auth()->user();
        $query = Appointment::where('user_id', $user->id)
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
        $services     = $user->appointmentServices()->active()->get();

        return view('appointments.list', compact('appointments', 'services'));
    }

    /**
     * Muestra/edita una cita concreta.
     */
    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        $appointment->load(['service', 'visitor', 'task', 'expediente']);
        return view('appointments.show', compact('appointment'));
    }

    /**
     * Actualiza una cita (mover fecha/hora, notas, expediente).
     */
    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

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

        if (isset($data['status']) && $data['status'] === 'cancelled') {
            $data['cancelled_at'] = now();
        }

        $appointment->update($data);

        // Actualizar la tarea si existe
        if ($appointment->task && (isset($data['appointment_date']) || isset($data['appointment_time']))) {
            $appointment->task->update(['due_date' => $appointment->appointment_date]);
        }

        return back()->with('success', 'Cita actualizada correctamente.');
    }

    /**
     * Cancela y elimina una cita.
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

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
     * API: agenda semanal en JSON para la vista de calendario.
     */
    public function agenda(Request $request)
    {
        $user  = auth()->user();
        $start = Carbon::parse($request->get('start', now()->startOfWeek()));
        $end   = Carbon::parse($request->get('end', now()->endOfWeek()));

        $appointments = Appointment::where('user_id', $user->id)
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
}
