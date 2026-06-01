<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\AppointmentSettings;
use App\Models\AppointmentVisitor;
use App\Models\User;
use App\Services\AppointmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicAppointmentController extends Controller
{
    public function __construct(private AppointmentAvailabilityService $availability) {}

    /**
     * Mapa público con todos los miembros con portal activo y coordenadas GPS.
     */
    public function map()
    {
        $members = User::whereHas('appointmentSettings', fn($q) => $q->where('is_public', true))
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->with(['appointmentSettings', 'appointmentServices' => fn($q) => $q->active()])
            ->get()
            ->filter(fn($u) => $u->appointmentServices->isNotEmpty())
            ->map(fn($u) => [
                'slug'         => $u->appointmentSettings->public_slug,
                'display_name' => $u->appointmentSettings->display_name ?: $u->name,
                'lat'          => $u->location_lat,
                'lng'          => $u->location_lng,
                'services'     => $u->appointmentServices->count(),
                'area'         => $u->working_area_name,
            ]);

        return view('public.appointments.map', compact('members'));
    }

    /**
     * Página pública de un miembro concreto.
     */
    public function member(string $slug)
    {
        $settings = AppointmentSettings::where('public_slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $member   = $settings->user;
        $services = $member->appointmentServices()->active()->orderBy('sort_order')->get();

        return view('public.appointments.member', compact('settings', 'member', 'services'));
    }

    /**
     * API: tramos disponibles para un servicio y fecha.
     */
    public function slots(AppointmentService $service, string $date)
    {
        $parsedDate = Carbon::parse($date);

        // Seguridad: no fechas pasadas ni miembro inactivo
        if ($parsedDate->isPast() && !$parsedDate->isToday()) {
            return response()->json(['slots' => []]);
        }

        $slots = $this->availability->getSlotsForDate($service, $parsedDate);
        return response()->json(['slots' => $slots]);
    }

    /**
     * API: días disponibles en un mes para un servicio.
     */
    public function availableDays(AppointmentService $service, int $year, int $month)
    {
        $days = $this->availability->getAvailableDaysInMonth($service, $year, $month);
        return response()->json(['available_days' => $days]);
    }

    /**
     * Formulario de reserva: mostrar.
     */
    public function book(Request $request, AppointmentService $service)
    {
        $settings = $service->user->appointmentSettings;

        if (!$settings || !$settings->is_public) {
            abort(404);
        }

        $date = $request->get('date') ? Carbon::parse($request->get('date')) : null;
        $time = $request->get('time');
        $slots = $date ? $this->availability->getSlotsForDate($service, $date) : [];

        return view('public.appointments.book', compact('service', 'settings', 'date', 'time', 'slots'));
    }

    /**
     * Formulario de reserva: guardar.
     */
    public function store(Request $request, AppointmentService $service)
    {
        $settings = $service->user->appointmentSettings;
        if (!$settings || !$settings->is_public) {
            abort(404);
        }

        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:150',
            'dni'           => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'city'          => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'observations'  => 'nullable|string|max:2000',
            'consent_email' => 'boolean',
            'consent_data'  => 'required|accepted',
            'consent_legal' => 'required|accepted',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string',
        ]);

        $date = Carbon::parse($data['appointment_date']);

        // Validar disponibilidad en tiempo real
        if (!$this->availability->isSlotAvailable($service, $date, $data['appointment_time'])) {
            return back()->withErrors(['appointment_time' => 'El tramo seleccionado ya no está disponible. Por favor, elige otro.'])->withInput();
        }

        // Crear visitante
        $visitor = AppointmentVisitor::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'dni'           => $data['dni'] ?? null,
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'city'          => $data['city'] ?? null,
            'postal_code'   => $data['postal_code'] ?? null,
            'observations'  => $data['observations'] ?? null,
            'consent_email' => $request->boolean('consent_email'),
            'consent_data'  => true,
            'consent_legal' => true,
            'ip_address'    => $request->ip(),
        ]);

        // Crear cita
        $appointment = Appointment::create([
            'localizador'         => Appointment::generateLocalizador(),
            'user_id'             => $service->user_id,
            'service_id'          => $service->id,
            'visitor_id'          => $visitor->id,
            'appointment_date'    => $date->toDateString(),
            'appointment_time'    => $data['appointment_time'] . ':00',
            'slot_duration_minutes' => $service->getEffectiveSlotDuration(),
            'status'              => 'confirmed',
        ]);

        // Generar tarea automática si está configurado
        if ($settings->auto_create_task) {
            $this->createTaskForAppointment($appointment, $settings);
        }

        // Email de confirmación al visitante (si consintió y tiene email)
        if ($visitor->consent_email && $visitor->email && $settings->email_confirmation) {
            try {
                \Mail::to($visitor->email)->send(new \App\Mail\AppointmentConfirmedMail($appointment));
            } catch (\Throwable $e) {
                \Log::warning("AppointmentConfirmed mail failed: " . $e->getMessage());
            }
        }

        // Email al miembro de nueva cita
        try {
            \Mail::to($service->user->email)->send(new \App\Mail\AppointmentNewRequestMail($appointment));
        } catch (\Throwable $e) {
            \Log::warning("AppointmentNewRequest mail failed: " . $e->getMessage());
        }

        return redirect()->route('public.appointments.confirm', $appointment->localizador);
    }

    /**
     * Página de confirmación con localizador.
     */
    public function confirm(string $localizador)
    {
        $appointment = Appointment::where('localizador', $localizador)
            ->with(['service', 'service.user', 'service.user.appointmentSettings', 'visitor'])
            ->firstOrFail();

        return view('public.appointments.confirm', compact('appointment'));
    }

    /**
     * Genera una tarea automática en el sistema para la cita.
     */
    protected function createTaskForAppointment(Appointment $appointment, AppointmentSettings $settings): void
    {
        try {
            $member = $appointment->member;

            // Determinar el expediente
            $expedienteId = $settings->default_expediente_id;

            // Crear la tarea
            $task = \App\Models\Task::create([
                'title'          => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'description'    => "**Visitante:** {$appointment->visitor->full_name}\n**Localizador:** {$appointment->localizador}\n**Servicio:** {$appointment->service->name}\n**Fecha:** {$appointment->appointment_date->format('d/m/Y')} a las {$appointment->appointment_time}",
                'status'         => 'pending',
                'priority'       => 'medium',
                'due_date'       => $appointment->appointment_date,
                'created_by_id'  => $member->id,
                'expediente_id'  => $expedienteId,
                'team_id'        => $member->favorite_team_id,
            ]);

            // Asignar al miembro
            $task->assignedUsers()->syncWithoutDetaching([
                $member->id => [
                    'assigned_at'    => now(),
                    'assigned_by_id' => $member->id,
                ],
            ]);

            // Actualizar la cita con el task_id y expediente_id
            $appointment->update([
                'task_id'       => $task->id,
                'expediente_id' => $expedienteId,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Error creando tarea para cita {$appointment->localizador}: " . $e->getMessage());
        }
    }
}
