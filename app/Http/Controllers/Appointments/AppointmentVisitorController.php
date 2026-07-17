<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentVisitor;
use App\Models\Team;
use Illuminate\Http\Request;

class AppointmentVisitorController extends Controller
{
    public function index(Request $request, Team $team)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'first_name');
        $sortDir = $request->input('sort_dir', 'asc');
        $filterName = $request->input('filter_name');
        $filterDni = $request->input('filter_dni');
        $filterCity = $request->input('filter_city');
        $filterMinAppointments = $request->input('filter_min_appointments');

        // Validar y limitar campos de ordenación
        $allowedSortFields = ['first_name', 'last_name', 'dni', 'email', 'phone', 'city', 'appointments_count'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'first_name';
        }
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'asc';

        // Obtener los visitantes que tienen citas con servicios de este equipo
        $visitors = AppointmentVisitor::whereHas('appointments', function ($query) use ($team) {
            $query->whereHas('service', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            });
        })
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        })
        ->when($filterName, function ($query, $filterName) {
            $query->where(function ($q) use ($filterName) {
                $q->where('first_name', 'like', "%{$filterName}%")
                  ->orWhere('last_name', 'like', "%{$filterName}%");
            });
        })
        ->when($filterDni, function ($query, $filterDni) {
            $query->where('dni', 'like', "%{$filterDni}%");
        })
        ->when($filterCity, function ($query, $filterCity) {
            $query->where('city', 'like', "%{$filterCity}%");
        })
        ->when($filterMinAppointments, function ($query, $filterMinAppointments) {
            $query->whereHas('appointments', function ($q) use ($team, $filterMinAppointments) {
                $q->whereHas('service', function ($sq) use ($team) {
                    $sq->where('team_id', $team->id);
                });
            });
        })
        ->withCount(['appointments' => function ($query) use ($team) {
            $query->whereHas('service', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            });
        }])
        ->when($filterMinAppointments, function ($query, $filterMinAppointments) {
            $query->having('appointments_count', '>=', (int)$filterMinAppointments);
        })
        ->when($sortBy === 'appointments_count', function ($query) use ($sortDir) {
            $query->orderBy('appointments_count', $sortDir);
        }, function ($query) use ($sortBy, $sortDir) {
            $query->orderBy($sortBy, $sortDir);
        })
        ->paginate(15)
        ->withQueryString();

        return view('appointments.visitors.index', compact('team', 'visitors', 'sortBy', 'sortDir', 'filterName', 'filterDni', 'filterCity', 'filterMinAppointments'));
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
