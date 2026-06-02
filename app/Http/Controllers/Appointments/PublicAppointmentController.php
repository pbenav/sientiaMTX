<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\AppointmentSettings;
use App\Models\AppointmentVisitor;
use App\Models\User;
use App\Rules\DniNie;
use App\Services\AppointmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicAppointmentController extends Controller
{
    public function __construct(private AppointmentAvailabilityService $availability) {}

    public function map()
    {
        // 1. Obtener miembros con coordenadas GPS y habilitados por el coordinador
        $rawMembers = User::whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->with(['appointmentSettings', 'appointmentServices'])
            ->get()
            ->filter(fn($u) => $u->hasAppointmentsEnabled());

        // 2. Inicializar de forma inteligente los que no tengan configuración o servicios
        foreach ($rawMembers as $u) {
            // Asegurar que tengan appointment_settings con is_public = true por defecto
            if (!$u->appointmentSettings) {
                $u->appointmentSettings()->create([
                    'public_slug' => \Illuminate\Support\Str::slug($u->name) . '-' . $u->id,
                    'display_name' => $u->name,
                    'is_public' => true,
                    'default_slot_duration' => 15,
                    'default_max_per_slot' => 1,
                    'auto_create_task' => true,
                    'email_confirmation' => true,
                ]);
                $u->unsetRelation('appointmentSettings'); // Forzar recarga de relación
            }

            // Asegurar que tengan al menos 1 servicio activo para que el portal sea funcional
            if ($u->appointmentServices()->active()->count() === 0) {
                $service = $u->appointmentServices()->create([
                    'name' => 'Consulta General',
                    'description' => 'Consulta o asesoramiento general de información.',
                    'duration_minutes' => 15,
                    'is_active' => true,
                    'price' => null,
                    'price_visible' => false,
                ]);

                // Crear horario por defecto de Lunes a Viernes de 09:00 a 14:00
                for ($day = 1; $day <= 5; $day++) {
                    \App\Models\AppointmentSchedule::create([
                        'user_id' => $u->id,
                        'service_id' => $service->id,
                        'day_of_week' => $day,
                        'start_time' => '09:00',
                        'end_time' => '14:00',
                        'slot_duration_minutes' => 15,
                        'max_per_slot' => 1,
                        'is_active' => true,
                    ]);
                }
                $u->unsetRelation('appointmentServices'); // Forzar recarga de relación
            }
        }

        // 3. Consultar la lista final limpia para el mapa público
        $members = User::whereHas('appointmentSettings', fn($q) => $q->where('is_public', true))
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->with(['appointmentSettings', 'appointmentServices' => fn($q) => $q->active()])
            ->get()
            ->filter(fn($u) => $u->hasAppointmentsEnabled())
            ->filter(fn($u) => $u->appointmentServices->isNotEmpty())
            ->map(fn($u) => [
                'slug'         => $u->appointmentSettings->public_slug,
                'display_name' => $u->appointmentSettings->display_name ?: $u->name,
                'lat'          => $u->location_lat,
                'lng'          => $u->location_lng,
                'services'     => $u->appointmentServices->count(),
                'area'         => $u->working_area_name ?: 'Área Territorial',
                'teams'        => $u->teams()
                    ->whereJsonContains('settings->has_appointments', true)
                    ->wherePivot('allow_appointments', true)
                    ->pluck('name')
                    ->toArray(),
            ]);

        $allTeams = $members->pluck('teams')->flatten()->unique()->sort()->values()->toArray();

        return view('public.appointments.map', compact('members', 'allTeams'));
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

        if (!$member->hasAppointmentsEnabled()) {
            abort(404);
        }

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

        if (!$settings || !$settings->is_public || !$service->user->hasAppointmentsEnabled()) {
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
        if (!$settings || !$settings->is_public || !$service->user->hasAppointmentsEnabled()) {
            abort(404);
        }

        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:150',
            'dni'           => ['nullable', 'string', 'max:20', new DniNie],
            'email'         => 'nullable|email:rfc,dns|max:255',
            'phone'         => ['nullable', 'string', 'max:20', 'regex:/^(\+?[0-9\s\-\.\(\)]{6,20})$/'],
            'city'          => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'observations'  => 'nullable|string|max:2000',
            'consent_email' => 'boolean',
            'consent_data'  => 'required|accepted',
            'consent_legal' => 'required|accepted',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string',
            'modality'         => 'required|string|in:presencial,jitsi,meet',
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
            'modality'            => $data['modality'],
        ]);

        // Generar tarea automática si está configurado
        if ($settings->auto_create_task) {
            $this->createTaskForAppointment($appointment, $settings);
        }

        // Sincronizar con Google Calendar si está habilitado
        if ($settings->google_calendar_enabled && $service->user->google_token) {
            $this->syncToGoogleCalendar($appointment);
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

            // Determinar la descripción de la tarea con enlace de videoconferencia si procede
            $description = "**Visitante:** {$appointment->visitor->full_name}\n"
                . "**Localizador:** {$appointment->localizador}\n"
                . "**Servicio:** {$appointment->service->name}\n"
                . "**Modalidad:** " . \App\Models\AppointmentService::MODALITIES[$appointment->modality] . "\n"
                . "**Fecha:** {$appointment->appointment_date->format('d/m/Y')} a las {$appointment->appointment_time}";

            if (in_array($appointment->modality, ['jitsi', 'meet'])) {
                $videoUrl = route('public.appointments.video.auth', $appointment) . '?localizador=' . $appointment->localizador;
                $description .= "\n\n💻 **Videoconferencia:** [Iniciar Videoconferencia]({$videoUrl}) (Modalidad: " . ucfirst($appointment->modality) . ")";
            }

            $task = \App\Models\Task::create([
                'title'          => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'description'    => $description,
                'status'         => 'pending',
                'priority'       => 'medium',
                'due_date'       => $appointment->appointment_date,
                'created_by_id'  => $member->id,
                'expediente_id'  => $expedienteId,
                'team_id'        => $member->favorite_team_id,
            ]);

            // Asignar al miembro
            $task->assignedTo()->syncWithoutDetaching([
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

    /**
     * Sincronizar cita con Google Calendar.
     */
    protected function syncToGoogleCalendar(Appointment $appointment): void
    {
        try {
            $member = $appointment->member;
            $googleService = new \App\Services\GoogleService();
            
            if ($googleService->setTokenForUser($member)) {
                $description = "Cita Previa: {$appointment->service->name}\n"
                             . "Ciudadano: {$appointment->visitor->full_name}\n"
                             . "Localizador: {$appointment->localizador}";

                $eventData = [
                    'summary' => '[CITA] ' . $appointment->visitor->full_name . ' - ' . $appointment->service->name,
                    'description' => $description,
                    'start' => [
                        'dateTime' => $appointment->appointment_datetime->format(\DateTime::RFC3339),
                        'timeZone' => config('app.timezone'),
                    ],
                    'end' => [
                        'dateTime' => $appointment->end_datetime->format(\DateTime::RFC3339),
                        'timeZone' => config('app.timezone'),
                    ],
                ];

                $eventId = $googleService->createEvent($eventData);

                if ($eventId) {
                    $appointment->update(['google_event_id' => $eventId]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Error sincronizando cita con Google Calendar: " . $e->getMessage());
        }
    }

    /**
     * Pantalla de autenticación para la videoconferencia.
     */
    public function videoAuth(Request $request, Appointment $appointment)
    {
        if (!in_array($appointment->modality, ['jitsi', 'meet'])) {
            abort(404);
        }

        $prefilledLocalizador = $request->query('localizador');

        return view('public.appointments.video_auth', compact('appointment', 'prefilledLocalizador'));
    }

    /**
     * Procesar acceso a la videoconferencia.
     */
    public function videoAccess(Request $request, Appointment $appointment)
    {
        if (!in_array($appointment->modality, ['jitsi', 'meet'])) {
            abort(404);
        }

        $request->validate(['localizador' => 'required|string']);

        if (strtoupper($request->localizador) !== $appointment->localizador) {
            return back()->withErrors(['localizador' => 'El localizador introducido es incorrecto.']);
        }

        session(['video_access_' . $appointment->id => true]);

        return redirect()->route('public.appointments.video.room', $appointment);
    }

    /**
     * Sala de videoconferencia pública.
     */
    public function videoRoom(Appointment $appointment)
    {
        if (!in_array($appointment->modality, ['jitsi', 'meet'])) {
            abort(404);
        }

        if (!session('video_access_' . $appointment->id)) {
            return redirect()->route('public.appointments.video.auth', $appointment);
        }

        return view('public.appointments.video_room', compact('appointment'));
    }

    /**
     * Buscar cita de videoconferencia por localizador
     */
    public function findVideoAppointment(Request $request)
    {
        $request->validate([
            'localizador' => 'required|string|max:50',
        ]);

        $localizador = strtoupper(trim($request->localizador));
        $appointment = Appointment::where('localizador', $localizador)->first();

        if (!$appointment) {
            return back()->withErrors(['localizador_search' => 'No se ha encontrado ninguna cita con ese localizador.']);
        }

        if (!in_array($appointment->modality, ['jitsi', 'meet'])) {
            return back()->withErrors(['localizador_search' => 'Esta cita no está configurada como videoconferencia (es presencial).']);
        }

        // Como ya lo escribió correctamente en la barra del mapa, le damos acceso directamente
        session(['video_access_' . $appointment->id => true]);

        return redirect()->route('public.appointments.video.room', $appointment);
    }
}
