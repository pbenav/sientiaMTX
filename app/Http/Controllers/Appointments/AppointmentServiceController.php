<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentService;
use App\Models\Team;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión de servicios de citas dentro de un equipo.
 *
 * Permite crear, editar, listar y eliminar servicios de citas (consultas, reuniones, etc.)
 * con configuración de modalidades (presencial, Jitsi, Meet), horarios de disponibilidad,
 * campos personalizados y cumplimiento de protección de datos (RGPD).
 *
 * Rutas asociadas:
 *   - GET /teams/{team}/appointments/services
 *   - GET /teams/{team}/appointments/services/create
 *   - POST /teams/{team}/appointments/services
 *   - GET /teams/{team}/appointments/services/{service}/edit
 *   - PUT/PATCH /teams/{team}/appointments/services/{service}
 *   - DELETE /teams/{team}/appointments/services/{service}
 */
class AppointmentServiceController extends Controller
{
    /**
     * Muestra la lista de servicios de citas del usuario dentro de un equipo.
     *
     * @param Team $team
     * @return \Illuminate\View\View
     */
    public function index(Team $team)
    {
        $services = auth()->user()->appointmentServices()
            ->where('team_id', $team->id)
            ->orderBy('sort_order')
            ->get();
        return view('appointments.services.index', compact('services', 'team'));
    }

    /**
     * Muestra el formulario de creación de un nuevo servicio de citas.
     *
     * @param Team $team
     * @return \Illuminate\View\View
     */
    public function create(Team $team)
    {
        return view('appointments.services.create', compact('team'));
    }

    /**
     * Crea un nuevo servicio de citas para el usuario autenticado.
     *
     * Valida todos los campos del formulario, procesa los campos personalizados
     * y de protección de datos, y guarda los horarios de disponibilidad del servicio.
     *
     * @param Team $team
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Team $team, Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'modality'           => 'required|array|min:1',
            'modality.*'         => 'string|in:presencial,jitsi,meet',
            'description'        => 'nullable|string',
            'duration_minutes'   => 'required|integer|min:5|max:480',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'max_per_slot'       => 'nullable|integer|min:1|max:100',
            'price'              => 'nullable|numeric|min:0',
            'price_visible'      => 'boolean',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer|min:0',
            'custom_fields'      => 'nullable|array',
            'custom_fields.*.id' => 'required_with:custom_fields|string',
            'custom_fields.*.name' => 'required_with:custom_fields|string|max:150',
            'custom_fields.*.type' => 'nullable|string|in:text,textarea,number,date',
            'custom_fields.*.is_required' => 'nullable|boolean',
            'data_protection' => 'nullable|array',
            'data_protection.enabled' => 'nullable|boolean',
            'data_protection.responsible' => 'nullable|string|max:255',
            'data_protection.address' => 'nullable|string|max:255',
            'data_protection.dpo_email' => 'nullable|email|max:255',
            'data_protection.url' => 'nullable|url|max:500',
            'data_protection.purpose' => 'nullable|string',
            'data_protection.template' => 'nullable|string',
        ]);

        // Asegurar que is_required sea booleano
        if (isset($data['custom_fields'])) {
            $data['custom_fields'] = array_values(array_map(function ($field) {
                $field['is_required'] = isset($field['is_required']) && $field['is_required'] == '1';
                return $field;
            }, $data['custom_fields']));
        }

        $data['team_id'] = $team->id;
        
        if (isset($data['data_protection'])) {
            $data['data_protection'] = [
                'enabled' => $request->boolean('data_protection.enabled'),
                'responsible' => $data['data_protection']['responsible'] ?? null,
                'address' => $data['data_protection']['address'] ?? null,
                'dpo_email' => $data['data_protection']['dpo_email'] ?? null,
                'url' => $data['data_protection']['url'] ?? null,
                'purpose' => $data['data_protection']['purpose'] ?? null,
                'template' => $data['data_protection']['template'] ?? null,
            ];
        } else {
            $data['data_protection'] = null;
        }

        $service = auth()->user()->appointmentServices()->create($data);

        // Guardar horarios de disponibilidad
        $schedules = $request->input('schedules', []);
        foreach ($schedules as $dayOfWeek => $sched) {
            if (isset($sched['is_active']) && $sched['is_active'] == '1') {
                if (isset($sched['tramos']) && is_array($sched['tramos'])) {
                    foreach ($sched['tramos'] as $tramo) {
                        $service->schedules()->create([
                            'user_id' => auth()->id(),
                            'day_of_week' => $dayOfWeek,
                            'start_time' => $tramo['start_time'] ?? '09:00',
                            'end_time' => $tramo['end_time'] ?? '14:00',
                            'slot_duration_minutes' => $service->getEffectiveSlotDuration(),
                            'max_per_slot' => $service->getEffectiveMaxPerSlot(),
                            'is_active' => true,
                        ]);
                    }
                } else {
                    $service->schedules()->create([
                        'user_id' => auth()->id(),
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $sched['start_time'] ?? '09:00',
                        'end_time' => $sched['end_time'] ?? '14:00',
                        'slot_duration_minutes' => $service->getEffectiveSlotDuration(),
                        'max_per_slot' => $service->getEffectiveMaxPerSlot(),
                        'is_active' => true,
                    ]);
                }
            }
        }

        return redirect()->route('appointments.services.index', $team)
            ->with('success', 'Servicio creado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un servicio de citas existente.
     *
     * Verifica que el servicio pertenezca al usuario autenticado y al equipo.
     *
     * @param Team $team
     * @param AppointmentService $service
     * @return \Illuminate\View\View
     */
    public function edit(Team $team, AppointmentService $service)
    {
        $this->authorizeService($service, $team);
        return view('appointments.services.edit', compact('service', 'team'));
    }

    /**
     * Actualiza un servicio de citas existente.
     *
     * Reemplaza los horarios de disponibilidad anteriores con los nuevos proporcionados.
     * Verifica la autorización del usuario sobre el servicio.
     *
     * @param Team $team
     * @param Request $request
     * @param AppointmentService $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Team $team, Request $request, AppointmentService $service)
    {
        $this->authorizeService($service, $team);

        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'modality'           => 'required|array|min:1',
            'modality.*'         => 'string|in:presencial,jitsi,meet',
            'description'        => 'nullable|string',
            'duration_minutes'   => 'required|integer|min:5|max:480',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'max_per_slot'       => 'nullable|integer|min:1|max:100',
            'price'              => 'nullable|numeric|min:0',
            'price_visible'      => 'boolean',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer|min:0',
            'custom_fields'      => 'nullable|array',
            'custom_fields.*.id' => 'required_with:custom_fields|string',
            'custom_fields.*.name' => 'required_with:custom_fields|string|max:150',
            'custom_fields.*.type' => 'nullable|string|in:text,textarea,number,date',
            'custom_fields.*.is_required' => 'nullable|boolean',
            'data_protection' => 'nullable|array',
            'data_protection.enabled' => 'nullable|boolean',
            'data_protection.responsible' => 'nullable|string|max:255',
            'data_protection.address' => 'nullable|string|max:255',
            'data_protection.dpo_email' => 'nullable|email|max:255',
            'data_protection.url' => 'nullable|url|max:500',
            'data_protection.purpose' => 'nullable|string',
            'data_protection.template' => 'nullable|string',
        ]);

        // Asegurar que is_required sea booleano
        if (isset($data['custom_fields'])) {
            $data['custom_fields'] = array_values(array_map(function ($field) {
                $field['is_required'] = isset($field['is_required']) && $field['is_required'] == '1';
                return $field;
            }, $data['custom_fields']));
        } else {
            $data['custom_fields'] = [];
        }

        $data['team_id'] = $team->id;
        
        if (isset($data['data_protection'])) {
            $data['data_protection'] = [
                'enabled' => $request->boolean('data_protection.enabled'),
                'responsible' => $data['data_protection']['responsible'] ?? null,
                'address' => $data['data_protection']['address'] ?? null,
                'dpo_email' => $data['data_protection']['dpo_email'] ?? null,
                'url' => $data['data_protection']['url'] ?? null,
                'purpose' => $data['data_protection']['purpose'] ?? null,
                'template' => $data['data_protection']['template'] ?? null,
            ];
        } else {
            $data['data_protection'] = null;
        }

        $service->update($data);

        // Actualizar horarios de disponibilidad
        $service->schedules()->delete();
        $schedules = $request->input('schedules', []);
        foreach ($schedules as $dayOfWeek => $sched) {
            if (isset($sched['is_active']) && $sched['is_active'] == '1') {
                if (isset($sched['tramos']) && is_array($sched['tramos'])) {
                    foreach ($sched['tramos'] as $tramo) {
                        $service->schedules()->create([
                            'user_id' => auth()->id(),
                            'day_of_week' => $dayOfWeek,
                            'start_time' => $tramo['start_time'] ?? '09:00',
                            'end_time' => $tramo['end_time'] ?? '14:00',
                            'slot_duration_minutes' => $service->getEffectiveSlotDuration(),
                            'max_per_slot' => $service->getEffectiveMaxPerSlot(),
                            'is_active' => true,
                        ]);
                    }
                } else {
                    $service->schedules()->create([
                        'user_id' => auth()->id(),
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $sched['start_time'] ?? '09:00',
                        'end_time' => $sched['end_time'] ?? '14:00',
                        'slot_duration_minutes' => $service->getEffectiveSlotDuration(),
                        'max_per_slot' => $service->getEffectiveMaxPerSlot(),
                        'is_active' => true,
                    ]);
                }
            }
        }

        return redirect()->route('appointments.services.index', $team)
            ->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Elimina un servicio de citas.
     *
     * No permite eliminar servicios que tengan citas activas (diferentes de canceladas).
     *
     * @param Team $team
     * @param AppointmentService $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team, AppointmentService $service)
    {
        $this->authorizeService($service, $team);

        if ($service->appointments()->whereNotIn('status', ['cancelled'])->exists()) {
            return back()->withErrors(['service' => 'No se puede eliminar un servicio con citas activas.']);
        }

        $service->delete();

        return redirect()->route('appointments.services.index', $team)
            ->with('success', 'Servicio eliminado.');
    }

    /**
     * Verifica que el servicio pertenezca al usuario autenticado y al equipo especificado.
     *
     * @param AppointmentService $service
     * @param Team $team
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function authorizeService(AppointmentService $service, Team $team): void
    {
        if ($service->user_id !== auth()->id() || $service->team_id !== $team->id) {
            abort(403);
        }
    }
}
