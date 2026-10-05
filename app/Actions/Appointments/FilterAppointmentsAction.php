<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterAppointmentsAction
{
    public function execute(Team $team, Request $request, bool $paginate = true)
    {
        $user = auth()->user();

        if ($request->has('clear')) {
            session()->forget("appointments_filters_{$team->id}");
        }

        // Filters Persistence
        $filterKeys = ['status', 'service_id', 'date_from', 'date_to', 'search', 'sort_by', 'sort_dir', 'per_page'];
        
        if (!$request->anyFilled($filterKeys) && !$request->hasAny($filterKeys) && !$request->has('clear')) {
            $sessionFilters = session("appointments_filters_{$team->id}", []);
            if (!empty($sessionFilters)) {
                $request->merge($sessionFilters);
            } else {
                $request->merge([
                    'date_from' => now()->toDateString(),
                    'date_to'   => now()->toDateString(),
                    'sort_by'   => 'appointment_date',
                    'sort_dir'  => 'asc',
                ]);
            }
        } else {
            session(["appointments_filters_{$team->id}" => $request->only($filterKeys)]);
        }

        $query = Appointment::where('appointments.user_id', $user->id)
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->with(['service', 'visitor', 'activity', 'task']);

        // Sorting
        $sortBy = $request->get('sort_by', 'appointment_date');
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'appointment_date') {
            $query->orderBy('appointments.appointment_date', $sortDir)->orderBy('appointments.appointment_time', $sortDir);
        } elseif (in_array($sortBy, ['created_at', 'localizador', 'status'])) {
            $query->orderBy('appointments.' . $sortBy, $sortDir);
        } elseif ($sortBy === 'visitor') {
            $query->join('appointment_visitors', 'appointments.visitor_id', '=', 'appointment_visitors.id')
                  ->orderBy('appointment_visitors.first_name', $sortDir)
                  ->select('appointments.*');
        } elseif ($sortBy === 'service') {
            $query->join('appointment_services', 'appointments.service_id', '=', 'appointment_services.id')
                  ->orderBy('appointment_services.name', $sortDir)
                  ->select('appointments.*');
        } elseif ($sortBy === 'time') {
            $taskSum = DB::table('time_logs')
                ->whereColumn('time_logs.task_id', 'appointments.task_id')
                ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)), 0)');

            $activitySum = DB::table('time_logs')
                ->whereColumn('time_logs.task_id', 'appointments.activity_id')
                ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)), 0)');

            $query->select('appointments.*')
                  ->selectSub($taskSum, 'task_time')
                  ->selectSub($activitySum, 'activity_time')
                  ->orderByRaw("(COALESCE(task_time, 0) + COALESCE(activity_time, 0)) $sortDir");
        } else {
            $query->orderBy('appointments.appointment_date', 'asc')->orderBy('appointments.appointment_time', 'asc');
        }

        // Filtering
        if ($request->filled('status')) {
            $query->whereIn('appointments.status', (array) $request->status);
        }

        if ($request->filled('service_id')) {
            $query->whereIn('appointments.service_id', (array) $request->service_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('appointments.appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('appointments.appointment_date', '<=', $request->date_to);
        }

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('appointments.localizador', 'like', "%{$search}%")
                  ->orWhereHas('visitor', function($vq) use ($search) {
                      $vq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('dni', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100, 500]) ? $perPage : 25;

        if (!$paginate) {
            return $query;
        }

        return $query->paginate($perPage)->appends($request->all());
    }
}
