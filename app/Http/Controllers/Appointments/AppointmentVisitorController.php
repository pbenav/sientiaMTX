<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentVisitor;
use App\Models\Team;
use Illuminate\Http\Request;

class AppointmentVisitorController extends Controller
{
    public function index(Team $team)
    {
        // Obtener los visitantes que tienen citas con servicios de este equipo
        $visitors = AppointmentVisitor::whereHas('appointments', function ($query) use ($team) {
            $query->whereHas('service', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            });
        })
        ->withCount(['appointments' => function ($query) use ($team) {
            $query->whereHas('service', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            });
        }])
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->paginate(15);

        return view('appointments.visitors.index', compact('team', 'visitors'));
    }

    public function edit(Team $team, AppointmentVisitor $visitor)
    {
        // Validar que el visitante tiene citas con el equipo (seguridad)
        $hasAppointments = $visitor->appointments()->whereHas('service', function ($q) use ($team) {
            $q->where('team_id', $team->id);
        })->exists();

        if (!$hasAppointments && !auth()->user()->is_admin) {
            abort(403, 'No tienes permiso para ver a este visitante.');
        }

        return view('appointments.visitors.edit', compact('team', 'visitor'));
    }

    public function update(Request $request, Team $team, AppointmentVisitor $visitor)
    {
        $hasAppointments = $visitor->appointments()->whereHas('service', function ($q) use ($team) {
            $q->where('team_id', $team->id);
        })->exists();

        if (!$hasAppointments && !auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:appointment_visitors,email,' . $visitor->id,
            'phone' => 'nullable|string|max:255',
            'dni' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        $visitor->update($validated);

        return redirect()->route('appointments.visitors.index', $team)
            ->with('success', 'Datos de la persona actualizados correctamente.');
    }

    public function destroy(Team $team, AppointmentVisitor $visitor)
    {
        $hasAppointments = $visitor->appointments()->whereHas('service', function ($q) use ($team) {
            $q->where('team_id', $team->id);
        })->exists();

        if (!$hasAppointments && !auth()->user()->is_admin) {
            abort(403);
        }

        // Si eliminamos el visitante, ¿qué pasa con sus citas? 
        // Tal vez no deberíamos permitir eliminarlo si tiene citas.
        // O si lo eliminamos, las citas asociadas se perderán. Generalmente es mejor no eliminar.
        if ($visitor->appointments()->count() > 0) {
            return redirect()->route('appointments.visitors.index', $team)
                ->with('error', 'No se puede eliminar la persona porque tiene citas asociadas.');
        }

        $visitor->delete();

        return redirect()->route('appointments.visitors.index', $team)
            ->with('success', 'Persona eliminada correctamente.');
    }
}
