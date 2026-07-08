<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentMetricsService
{
    public function getOverview(int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $total = DB::table('appointments')
            ->whereBetween('appointment_date', [$startDate, Carbon::now()])
            ->count();

        $byStatus = DB::table('appointments')
            ->whereBetween('appointment_date', [$startDate, Carbon::now()])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $confirmed = $byStatus->get('confirmed', 0);
        $cancelled = $byStatus->get('cancelled', 0);
        $noShow = $byStatus->get('no_show', 0);
        $completed = $byStatus->get('completed', 0);

        return [
            'total' => $total,
            'confirmed' => (int) $confirmed,
            'cancelled' => (int) $cancelled,
            'no_show' => (int) $noShow,
            'completed' => (int) $completed,
            'confirmation_rate' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
            'no_show_rate' => $total > 0 ? round(($noShow / $total) * 100, 1) : 0,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    public function getBookingTrends(int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('appointments')
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $labels = [];
        $data = [];
        $cursor = $startDate->copy();
        while ($cursor <= Carbon::now()) {
            $dayStr = $cursor->toDateString();
            $row = $rows->firstWhere('day', $dayStr);
            $labels[] = $cursor->format('d/M');
            $data[] = $row ? (int) $row->count : 0;
            $cursor->addDay();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getDistributionByService(int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('appointments')
            ->join('appointment_services', 'appointments.service_id', '=', 'appointment_services.id')
            ->whereBetween('appointments.appointment_date', [$startDate, Carbon::now()])
            ->selectRaw('appointment_services.name as service_name, COUNT(*) as count')
            ->groupBy('appointment_services.id', 'appointment_services.name')
            ->orderByDesc('count')
            ->get();

        return $rows->map(function ($r) {
            return ['service' => $r->service_name, 'count' => (int) $r->count];
        })->toArray();
    }

    public function getPeakHours(int $days = 7): array
    {
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $count = DB::table('appointments')
                ->whereBetween('created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
                ->whereRaw('HOUR(appointment_time) = ?', [$h])
                ->count();
            $hours[] = ['hour' => $h, 'label' => sprintf('%02d:00', $h), 'count' => $count];
        }

        return $hours;
    }

    public function getPeakDays(int $days = 30): array
    {
        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        $rows = DB::table('appointments')
            ->whereBetween('appointment_date', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->selectRaw('DAYOFWEEK(appointment_date) as day_of_week, COUNT(*) as count')
            ->groupBy('day_of_week')
            ->get();

        $result = [];
        for ($d = 1; $d <= 7; $d++) {
            $row = $rows->firstWhere('day_of_week', $d);
            $result[] = [
                'day' => $d,
                'name' => $dayNames[$d - 1],
                'count' => $row ? (int) $row->count : 0,
            ];
        }

        return $result;
    }

    public function getCancellationTrend(int $weeks = 8): array
    {
        $startDate = Carbon::now()->copy()->subWeeks($weeks);

        $rows = DB::table('appointments')
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('YEARWEEK(created_at) as year_week, COUNT(*) as total, SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        $labels = [];
        $rates = [];
        $rows->each(function ($r) use (&$labels, &$rates) {
            $labels[] = 'W' . $r->year_week;
            $rates[] = $r->total > 0 ? round(($r->cancelled / $r->total) * 100, 1) : 0;
        });

        return ['labels' => $labels, 'data' => $rates];
    }

    public function getNoShowTrend(int $weeks = 8): array
    {
        $startDate = Carbon::now()->copy()->subWeeks($weeks);

        $rows = DB::table('appointments')
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('YEARWEEK(created_at) as year_week, COUNT(*) as total, SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_show')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        $labels = [];
        $rates = [];
        $rows->each(function ($r) use (&$labels, &$rates) {
            $labels[] = 'W' . $r->year_week;
            $rates[] = $r->total > 0 ? round(($r->no_show / $r->total) * 100, 1) : 0;
        });

        return ['labels' => $labels, 'data' => $rates];
    }

    public function getReturnRate(int $days = 90): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $totalVisitors = DB::table('appointments')
            ->whereBetween('appointment_date', [$startDate, Carbon::now()])
            ->selectRaw('COUNT(DISTINCT visitor_id) as total')
            ->value('total') ?? 0;

        $returningVisitors = DB::table('appointments')
            ->whereBetween('appointment_date', [$startDate, Carbon::now()])
            ->selectRaw('visitor_id, COUNT(*) as appt_count')
            ->groupBy('visitor_id')
            ->havingRaw('appt_count > 1')
            ->count();

        return [
            'total_visitors' => (int) $totalVisitors,
            'returning_visitors' => (int) $returningVisitors,
            'return_rate' => $totalVisitors > 0 ? round(($returningVisitors / $totalVisitors) * 100, 1) : 0,
        ];
    }

    public function getAppointmentStats(int $teamId, int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $stats = DB::table('appointments')
            ->join('users', 'appointments.user_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereBetween('appointments.appointment_date', [$startDate, Carbon::now()])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            ')
            ->first();

        $total = $stats->total ?? 0;

        return [
            'total' => $total,
            'confirmed' => (int) ($stats->confirmed ?? 0),
            'cancelled' => (int) ($stats->cancelled ?? 0),
            'no_show' => (int) ($stats->no_show ?? 0),
            'completed' => (int) ($stats->completed ?? 0),
            'no_show_rate' => $total > 0 ? round((($stats->no_show ?? 0) / $total) * 100, 1) : 0,
            'cancellation_rate' => $total > 0 ? round((($stats->cancelled ?? 0) / $total) * 100, 1) : 0,
        ];
    }
}
