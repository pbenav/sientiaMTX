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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PublicAppointmentController extends Controller
{
    public function __construct(private AppointmentAvailabilityService $availability) {}

    public function map()
    {
        // 1. Obtener miembros con coordenadas GPS y habilitados por el coordinador
        $rawMembers = User::whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->get()
            ->filter(fn($u) => $u->hasAppointmentsEnabled());

        // 2. Inicializar de forma inteligente los que no tengan configuración o servicios para cada equipo activo
        foreach ($rawMembers as $u) {
            $userTeams = $u->teams()
                ->whereJsonContains('settings->has_appointments', true)
                ->wherePivot('allow_appointments', true)
                ->get();

            foreach ($userTeams as $team) {
                // Asegurar que tengan appointment_settings con is_public = true por defecto para este equipo
                $settingsExist = $u->appointmentSettings()->where('team_id', $team->id)->exists();
                if (!$settingsExist) {
                    $u->appointmentSettings()->create([
                        'team_id' => $team->id,
                        'public_slug' => \Illuminate\Support\Str::slug($u->name) . '-' . $u->id . '-' . $team->id,
                        'display_name' => $u->name,
                        'is_public' => true,
                        'default_slot_duration' => 15,
                        'default_max_per_slot' => 1,
                        'auto_create_task' => true,
                        'email_confirmation' => true,
                    ]);
                }

                // Asegurar que tengan al menos 1 servicio activo para este equipo
                $servicesExist = $u->appointmentServices()->where('team_id', $team->id)->active()->exists();
                if (!$servicesExist) {
                    $service = $u->appointmentServices()->create([
                        'team_id' => $team->id,
                        'name' => 'Consulta General',
                        'description' => 'Consulta o asesoramiento general de información.',
                        'duration_minutes' => 15,
                        'is_active' => true,
                        'price' => null,
                        'price_visible' => false,
                        'modality' => ['presencial'],
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
                }
            }
        }

        // 3. Consultar la lista de configuraciones públicas
        $settings = AppointmentSettings::where('is_public', true)
            ->whereHas('user', fn($q) => $q->whereNotNull('location_lat')->whereNotNull('location_lng'))
            ->with(['user', 'team'])
            ->get()
            ->filter(fn($s) => $s->user->hasAppointmentsEnabledForTeam($s->team_id));

        $members = $settings->map(fn($s) => [
            'slug'         => $s->public_slug,
            'display_name' => $s->display_name ?: $s->user->name,
            'lat'          => $s->user->location_lat,
            'lng'          => $s->user->location_lng,
            'services'     => $s->user->appointmentServices()->where('team_id', $s->team_id)->active()->count(),
            'area'         => $s->user->working_area_name ?: 'Área Territorial',
            'teams'        => $s->team ? [$s->team->name] : [],
        ])
        ->filter(fn($item) => $item['services'] > 0)
        ->values();

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

        if (!$member->hasAppointmentsEnabledForTeam($settings->team_id)) {
            abort(404);
        }

        $services = $member->appointmentServices()
            ->where('team_id', $settings->team_id)
            ->active()
            ->orderBy('sort_order')
            ->get();

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
        $settings = $service->user->appointmentSettingsForTeam($service->team_id);

        if (!$settings || !$settings->is_public || !$service->user->hasAppointmentsEnabledForTeam($service->team_id)) {
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
        $settings = $service->user->appointmentSettingsForTeam($service->team_id);
        if (!$settings || !$settings->is_public || !$service->user->hasAppointmentsEnabledForTeam($service->team_id)) {
            abort(404);
        }

        $validationRules = [
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
        ];

        if (!empty($service->custom_fields)) {
            $validationRules['custom_fields_values'] = 'nullable|array';
            foreach ($service->custom_fields as $field) {
                $rule = $field['is_required'] ? 'required' : 'nullable';
                if ($field['type'] === 'number') {
                    $rule .= '|numeric';
                } elseif ($field['type'] === 'date') {
                    $rule .= '|date';
                } else {
                    $rule .= '|string|max:2000';
                }
                $validationRules['custom_fields_values.' . $field['id']] = $rule;
            }
        }

        $data = $request->validate($validationRules);

        $date = Carbon::parse($data['appointment_date']);

        // Traducir caracteres árabes si existen
        $firstName = $this->transliterateArabic($data['first_name']);
        $lastName = $this->transliterateArabic($data['last_name']);

        if (!empty($data['email'])) {
            $existingEmailVisitor = \App\Models\AppointmentVisitor::where('email', $data['email'])->first();
            if ($existingEmailVisitor) {
                $isDifferentPerson = false;
                
                if (!empty($data['dni']) && !empty($existingEmailVisitor->dni) && mb_strtoupper($data['dni']) !== mb_strtoupper($existingEmailVisitor->dni)) {
                    $isDifferentPerson = true;
                }
                // Se elimina la validación estricta de nombre y apellidos para permitir erratas, diminutivos o pequeños cambios.
                
                if ($isDifferentPerson) {
                    return back()->withErrors(['email' => 'Este correo electrónico ya está registrado a nombre de otra persona. No se permite usar el mismo correo para distintas personas.'])->withInput();
                }
            }
        }

        // Bloquear el tramo horario específico para evitar que múltiples personas (o clics dobles)
        // reserven el mismo espacio simultáneamente, causando una condición de carrera.
        $lockKey = 'appointment_slot_' . $service->id . '_' . $date->format('Ymd') . '_' . str_replace(':', '', $data['appointment_time']);
        
        // Evitamos usar el driver 'file' para los bloqueos, ya que puede dar fallos de 'fopen' si los directorios temporales no existen
        $cacheStore = config('cache.default') === 'file' ? 'database' : null;

        return Cache::store($cacheStore)->lock($lockKey, 10)->block(5, function () use ($request, $service, $data, $settings, $date, $firstName, $lastName) {
            return DB::transaction(function () use ($request, $service, $data, $settings, $date, $firstName, $lastName) {
                // Validar disponibilidad en tiempo real dentro de la transacción
                if (!$this->availability->isSlotAvailable($service, $date, $data['appointment_time'])) {
                    return back()->withErrors(['appointment_time' => 'El tramo seleccionado ya no está disponible. Por favor, elige otro.'])->withInput();
                }

                // Buscar si existe un visitante previo con coincidencia estricta para evitar cruce de datos
                $visitorQuery = AppointmentVisitor::query();
                if (!empty($data['email'])) {
                    $visitorQuery->where('email', $data['email']);
                } elseif (!empty($data['dni'])) {
                    $visitorQuery->where('dni', $data['dni']);
                } else {
                    $visitorQuery->where('first_name', $firstName)
                                 ->where('last_name', $lastName)
                                 ->where('phone', $data['phone']);
                }

                // Bloqueamos la fila del visitante para actualización si existe
                $visitor = $visitorQuery->lockForUpdate()->first();

                if ($visitor) {
                    // Restricción: No puede tener más de una cita el mismo día para este servicio específico
                    $existingAppointment = Appointment::where('visitor_id', $visitor->id)
                        ->where('appointment_date', $date->toDateString())
                        ->whereIn('status', ['confirmed', 'scheduled', 'pending'])
                        ->where('service_id', $service->id)
                        ->first();

                    if ($existingAppointment) {
                        return back()->withErrors(['appointment_date' => 'Ya tienes una cita programada para este servicio en la fecha indicada.'])
                                     ->with('existing_appointment_localizador', $existingAppointment->localizador)
                                     ->withInput();
                    }

                    // Actualizar datos del visitante existente
                    $visitor->update([
                        'first_name'    => $firstName,
                        'last_name'     => $lastName,
                        'dni'           => $data['dni'] ?? $visitor->dni,
                        'phone'         => $data['phone'] ?? $visitor->phone,
                        'city'          => $data['city'] ?? $visitor->city,
                        'postal_code'   => $data['postal_code'] ?? $visitor->postal_code,
                        'observations'  => $data['observations'] ?? $visitor->observations,
                        'consent_email' => $request->boolean('consent_email'),
                        'ip_address'    => $request->ip(),
                    ]);
                } else {
                    // Crear nuevo visitante
                    $visitor = AppointmentVisitor::create([
                        'first_name'    => $firstName,
                        'last_name'     => $lastName,
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
                }

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
            'custom_fields_values' => $data['custom_fields_values'] ?? null,
        ]);

        // Generar tarea automática si está configurado
        if ($settings->auto_create_task) {
            $this->createTaskForAppointment($appointment, $settings);
        }

        // Sincronizar con Google Calendar si está habilitado en el servicio
        if ($service->sync_to_google_calendar && $service->user->google_token) {
            $this->syncToGoogleCalendar($appointment);
        }

                // Sincronizar con Google Tasks si está habilitado en el servicio
                if ($service->sync_to_google_tasks && $service->user->google_token) {
                    $this->syncToGoogleTasks($appointment);
                }

                // Email de confirmación al visitante (si consintió y tiene email)
                if ($visitor->consent_email && $visitor->email && $settings->email_confirmation) {
                    try {
                        \Mail::to($visitor->email)->locale(app()->getLocale())->send(new \App\Mail\AppointmentConfirmedMail($appointment));
                    } catch (\Throwable $e) {
                        \Log::warning("AppointmentConfirmed mail failed: " . $e->getMessage());
                    }
                }

                // Email al miembro de nueva cita
                try {
                    \Mail::to($service->user->email)->locale($service->user->preferredLocale())->send(new \App\Mail\AppointmentNewRequestMail($appointment));
                } catch (\Throwable $e) {
                    \Log::warning("AppointmentNewRequest mail failed: " . $e->getMessage());
                }

                return redirect()->route('public.appointments.confirm', $appointment->localizador);
            });
        });
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

            if (!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields)) {
                $description .= "\n\n**Información Adicional:**\n";
                foreach ($appointment->service->custom_fields as $field) {
                    $val = $appointment->custom_fields_values[$field['id']] ?? '';
                    if (!empty($val)) {
                        $description .= "- **{$field['name']}:** {$val}\n";
                    }
                }
            }

            if (in_array($appointment->modality, ['jitsi', 'meet'])) {
                $videoUrl = route('public.appointments.video.auth', $appointment) . '?localizador=' . $appointment->localizador;
                $description .= "\n\n💻 **Videoconferencia:** [Iniciar Videoconferencia]({$videoUrl}) (Modalidad: " . ucfirst($appointment->modality) . ")";
            }

            $activity = \App\Models\Activity::create([
                'uuid'           => \Illuminate\Support\Str::uuid()->toString(),
                'title'          => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'description'    => $description,
                'status'         => ['value' => 'scheduled'],
                'priority'       => 'medium',
                'type'           => 'meeting',
                'due_date'       => $appointment->end_datetime,
                'scheduled_date' => $appointment->appointment_datetime,
                'original_due_date' => clone $appointment->end_datetime,
                'created_by_id'  => $member->id,
                'expediente_id'  => $expedienteId,
                'team_id'        => $appointment->service->team_id ?? $member->favorite_team_id,
                'metadata'       => [
                    'is_ephemeral' => true,
                    'location'     => \App\Models\AppointmentService::MODALITIES[$appointment->modality] ?? $appointment->modality,
                ],
            ]);

            // Asignar al miembro
            $activity->assignments()->create([
                'user_id' => $member->id,
                'assigned_by_id' => $member->id,
                'assigned_at' => now(),
            ]);

            // Actualizar la cita con el activity_id y expediente_id
            $appointment->update([
                'activity_id'   => $activity->id,
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

                if (!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields)) {
                    $description .= "\n\nInformación Adicional:\n";
                    foreach ($appointment->service->custom_fields as $field) {
                        $val = $appointment->custom_fields_values[$field['id']] ?? '';
                        if (!empty($val)) {
                            $description .= "- {$field['name']}: {$val}\n";
                        }
                    }
                }

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
     * Sincronizar cita con Google Tasks.
     */
    protected function syncToGoogleTasks(Appointment $appointment): void
    {
        try {
            $member = $appointment->member;
            $googleService = new \App\Services\GoogleService();
            
            if ($googleService->setTokenForUser($member)) {
                $description = "Cita Previa: {$appointment->service->name}\n"
                             . "Ciudadano: {$appointment->visitor->full_name}\n"
                             . "Localizador: {$appointment->localizador}\n"
                             . "Día: {$appointment->appointment_date->format('d/m/Y')}\n"
                             . "Hora: {$appointment->appointment_time}";

                if (!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields)) {
                    $description .= "\n\nInformación Adicional:\n";
                    foreach ($appointment->service->custom_fields as $field) {
                        $val = $appointment->custom_fields_values[$field['id']] ?? '';
                        if (!empty($val)) {
                            $description .= "- {$field['name']}: {$val}\n";
                        }
                    }
                }

                $taskData = [
                    'title' => '[CITA] ' . $appointment->visitor->full_name . ' - ' . $appointment->service->name,
                    'notes' => $description,
                    'due'   => $appointment->appointment_datetime->format(\DateTime::RFC3339),
                ];

                $taskId = $googleService->createTask($taskData);

                if ($taskId) {
                    $appointment->update(['google_task_id' => $taskId]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Error sincronizando cita con Google Tasks: " . $e->getMessage());
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

    public function edit(string $localizador)
    {
        $appointment = Appointment::where('localizador', $localizador)
            ->with(['service', 'visitor'])
            ->firstOrFail();

        $service = $appointment->service;
        $settings = $service->user->appointmentSettingsForTeam($service->team_id);

        if (!$settings || !$settings->is_public || !$service->user->hasAppointmentsEnabledForTeam($service->team_id)) {
            abort(404);
        }

        $date = Carbon::parse($appointment->appointment_date);
        $slots = $this->availability->getSlotsForDate($service, $date);

        return view('public.appointments.edit', compact('appointment', 'service', 'settings', 'slots'));
    }

    public function update(Request $request, string $localizador)
    {
        $appointment = Appointment::where('localizador', $localizador)
            ->with(['service', 'visitor'])
            ->firstOrFail();

        $service = $appointment->service;
        $settings = $service->user->appointmentSettingsForTeam($service->team_id);
        if (!$settings || !$settings->is_public || !$service->user->hasAppointmentsEnabledForTeam($service->team_id)) {
            abort(404);
        }

        $validationRules = [
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:150',
            'dni'           => ['nullable', 'string', 'max:20', new DniNie],
            'email'         => 'nullable|email:rfc,dns|max:255',
            'phone'         => ['nullable', 'string', 'max:20', 'regex:/^(\+?[0-9\s\-\.\(\)]{6,20})$/'],
            'city'          => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'observations'  => 'nullable|string|max:2000',
            'consent_email' => 'boolean',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string',
            'modality'         => 'required|string|in:presencial,jitsi,meet',
        ];

        if (!empty($service->custom_fields)) {
            $validationRules['custom_fields_values'] = 'nullable|array';
            foreach ($service->custom_fields as $field) {
                $rule = $field['is_required'] ? 'required' : 'nullable';
                if ($field['type'] === 'number') {
                    $rule .= '|numeric';
                } elseif ($field['type'] === 'date') {
                    $rule .= '|date';
                } else {
                    $rule .= '|string|max:2000';
                }
                $validationRules['custom_fields_values.' . $field['id']] = $rule;
            }
        }

        $data = $request->validate($validationRules);

        $newDate = Carbon::parse($data['appointment_date']);
        $newTime = $data['appointment_time'];

        // Traducir caracteres árabes si existen
        $firstName = $this->transliterateArabic($data['first_name']);
        $lastName = $this->transliterateArabic($data['last_name']);

        if (!empty($data['email'])) {
            $existingEmailVisitor = \App\Models\AppointmentVisitor::where('email', $data['email'])
                ->where('id', '!=', $appointment->visitor_id)
                ->first();
                
            if ($existingEmailVisitor) {
                $isDifferentPerson = false;
                
                if (!empty($data['dni']) && !empty($existingEmailVisitor->dni) && mb_strtoupper($data['dni']) !== mb_strtoupper($existingEmailVisitor->dni)) {
                    $isDifferentPerson = true;
                }
                // Se elimina la validación estricta de nombre y apellidos para permitir erratas, diminutivos o pequeños cambios.
                
                if ($isDifferentPerson) {
                    return back()->withErrors(['email' => 'Este correo electrónico ya está registrado a nombre de otra persona. No se permite usar el mismo correo para distintas personas.'])->withInput();
                }
            }
        }

        // Bloquear el tramo horario de destino para evitar condiciones de carrera si varios intentan ocupar el mismo hueco
        $lockKey = 'appointment_slot_' . $service->id . '_' . $newDate->format('Ymd') . '_' . str_replace(':', '', $newTime);

        // Evitamos usar el driver 'file' para los bloqueos
        $cacheStore = config('cache.default') === 'file' ? 'database' : null;

        return Cache::store($cacheStore)->lock($lockKey, 10)->block(5, function () use ($request, $appointment, $service, $data, $newDate, $newTime, $firstName, $lastName) {
            return DB::transaction(function () use ($request, $appointment, $service, $data, $newDate, $newTime, $firstName, $lastName) {
                // Volver a cargar el appointment con bloqueo para evitar actualizaciones concurrentes
                $appointment = Appointment::where('id', $appointment->id)->lockForUpdate()->first();

                $isOwnSlot = $appointment->appointment_date->eq($newDate) && $appointment->appointment_time === $newTime . ':00';

                if (!$isOwnSlot && !$this->availability->isSlotAvailable($service, $newDate, $newTime)) {
                    return back()->withErrors(['appointment_time' => 'El tramo seleccionado ya no está disponible. Por favor, elige otro.'])->withInput();
                }

                // Restricción: No puede tener más de una cita el mismo día para este servicio específico (excluyendo la propia)
                $existingOtherAppointment = Appointment::where('visitor_id', $appointment->visitor_id)
                    ->where('id', '!=', $appointment->id)
                    ->where('appointment_date', $newDate->toDateString())
                    ->whereIn('status', ['confirmed', 'scheduled', 'pending'])
                    ->where('service_id', $service->id)
                    ->first();

                if ($existingOtherAppointment) {
                    return back()->withErrors(['appointment_date' => 'Ya tienes otra cita programada para este servicio en la nueva fecha.'])
                                 ->with('existing_appointment_localizador', $existingOtherAppointment->localizador)
                                 ->withInput();
                }

                $originalDate = clone $appointment->appointment_date;
                $originalTime = $appointment->appointment_time;

                $appointment->visitor->update([
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'dni'           => $data['dni'] ?? null,
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'city'          => $data['city'] ?? null,
            'postal_code'   => $data['postal_code'] ?? null,
            'observations'  => $data['observations'] ?? null,
            'consent_email' => $request->boolean('consent_email'),
        ]);

        $appointment->update([
            'appointment_date'    => $newDate->toDateString(),
            'appointment_time'    => $newTime . ':00',
            'modality'            => $data['modality'],
            'custom_fields_values' => $data['custom_fields_values'] ?? null,
        ]);

        if ($appointment->task) {
            $appointment->task->update([
                'title'    => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'due_date' => $appointment->end_datetime,
            ]);
        }
        if ($appointment->activity) {
            $appointment->activity->update([
                'title'          => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'due_date'       => $appointment->end_datetime,
                'scheduled_date' => $appointment->appointment_datetime,
            ]);
        }

        if ($appointment->google_event_id || $appointment->google_task_id) {
            \App\Jobs\SyncAppointmentWithGoogleJob::dispatch($appointment);
        }

        $dateChanged = !$originalDate->eq($newDate);
        $timeChanged = $originalTime !== $newTime . ':00';

        if (($dateChanged || $timeChanged) && $appointment->visitor->consent_email && $appointment->visitor->email) {
            try {
                \Mail::to($appointment->visitor->email)
                    ->locale(app()->getLocale())
                    ->send(new \App\Mail\AppointmentModifiedMail($appointment));
            } catch (\Throwable $e) {
                \Log::warning("AppointmentModified mail failed from public update: " . $e->getMessage());
            }
        }

        return redirect()->route('public.appointments.confirm', $appointment->localizador)
            ->with('success', '¡Cita modificada correctamente!');
            });
        });
    }

    /**
     * Busca y retorna los datos de un visitante si el email ya existe.
     */
    public function getVisitorByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Por motivos estrictos de privacidad (RGPD) y prevención de enumeración de datos
        // no debemos devolver información personal en base a un simple email.
        // El autocompletado inseguro queda desactivado desde el servidor.
        return response()->json(['found' => false]);
    }

    /**
     * Helper to add a Latin transliteration to Arabic names.
     */
    protected function transliterateArabic(?string $text): ?string
    {
        if (!$text) {
            return $text;
        }

        if (preg_match('/\p{Arabic}/u', $text)) {
            // Utilizamos el transliterador para convertir caracteres árabes a latinos
            if (class_exists(\Transliterator::class)) {
                $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
                $latin = $transliterator ? $transliterator->transliterate($text) : \Illuminate\Support\Str::transliterate($text);
            } else {
                $latin = \Illuminate\Support\Str::transliterate($text);
            }
            
            // Limpiamos caracteres extraños (como tildes o comillas que pueda generar la transliteración)
            $latin = preg_replace('/[^a-zA-Z\s]/', '', $latin);
            $latin = ucwords(strtolower(trim($latin)));
            
            if ($latin) {
                return trim($text) . ' (' . $latin . ')';
            }
        }

        return trim($text);
    }
}
