<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentService;
use App\Models\Team;
use Illuminate\Http\Request;

class AppointmentServiceController extends Controller
{
    public function index(Team $team)
    {
        $services = auth()->user()->appointmentServices()
            ->where('team_id', $team->id)
            ->orderBy('sort_order')
            ->get();
        return view('appointments.services.index', compact('services', 'team'));
    }

    public function create(Team $team)
    {
        return view('appointments.services.create', compact('team'));
    }

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

    public function edit(Team $team, AppointmentService $service)
    {
        $this->authorizeService($service, $team);
        return view('appointments.services.edit', compact('service', 'team'));
    }

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

    private function authorizeService(AppointmentService $service, Team $team): void
    {
        if ($service->user_id !== auth()->id() || $service->team_id !== $team->id) {
            abort(403);
        }
    }
}
