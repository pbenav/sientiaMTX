<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentService;
use Illuminate\Http\Request;

class AppointmentServiceController extends Controller
{
    public function index()
    {
        $services = auth()->user()->appointmentServices()->orderBy('sort_order')->get();
        return view('appointments.services.index', compact('services'));
    }

    public function create()
    {
        return view('appointments.services.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'description'        => 'nullable|string',
            'duration_minutes'   => 'required|integer|min:5|max:480',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'max_per_slot'       => 'nullable|integer|min:1|max:100',
            'price'              => 'nullable|numeric|min:0',
            'price_visible'      => 'boolean',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer|min:0',
        ]);

        $service = auth()->user()->appointmentServices()->create($data);

        // Guardar horarios de disponibilidad
        $schedules = $request->input('schedules', []);
        foreach ($schedules as $dayOfWeek => $sched) {
            if (isset($sched['is_active']) && $sched['is_active'] == '1') {
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

        return redirect()->route('appointments.services.index')
            ->with('success', 'Servicio creado correctamente.');
    }

    public function edit(AppointmentService $service)
    {
        $this->authorizeService($service);
        return view('appointments.services.edit', compact('service'));
    }

    public function update(Request $request, AppointmentService $service)
    {
        $this->authorizeService($service);

        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'description'        => 'nullable|string',
            'duration_minutes'   => 'required|integer|min:5|max:480',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'max_per_slot'       => 'nullable|integer|min:1|max:100',
            'price'              => 'nullable|numeric|min:0',
            'price_visible'      => 'boolean',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer|min:0',
        ]);

        $service->update($data);

        // Actualizar horarios de disponibilidad
        $service->schedules()->delete();
        $schedules = $request->input('schedules', []);
        foreach ($schedules as $dayOfWeek => $sched) {
            if (isset($sched['is_active']) && $sched['is_active'] == '1') {
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

        return redirect()->route('appointments.services.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(AppointmentService $service)
    {
        $this->authorizeService($service);

        if ($service->appointments()->whereNotIn('status', ['cancelled'])->exists()) {
            return back()->withErrors(['service' => 'No se puede eliminar un servicio con citas activas.']);
        }

        $service->delete();

        return redirect()->route('appointments.services.index')
            ->with('success', 'Servicio eliminado.');
    }

    private function authorizeService(AppointmentService $service): void
    {
        if ($service->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
