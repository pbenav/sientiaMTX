<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de métricas relacionadas con citas médicas.
 *
 * Proporciona indicadores de volumen, tasas de confirmación, cancelación,
 * no-show, tendencias de reserva, distribución por servicio, horas y días
 * pico, y tasas de retorno de pacientes. También incluye métricas específicas
 * por equipo.
 */
class AppointmentMetricsService
{
    /**
     * Obtiene un resumen general de las citas en el periodo indicado.
     *
     * @param int $days Número de días hacia atrás para analizar (por defecto 30).
     * @return array Resumen con total, conteos por estado, y tasas de confirmación, cancelación, no-show y completado.
     */
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

    /**
     * Obtiene las tendencias de creación de citas por día.
     *
     * @param int $days Número de días hacia atrás para analizar (por defecto 30).
     * @return array Array de días con etiqueta y conteo de citas creadas.
     */
    public function getBookingTrends(int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('appointments')
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $result = [];
        $cursor = $startDate->copy();
        while ($cursor <= Carbon::now()) {
            $dayStr = $cursor->toDateString();
            $row = $rows->firstWhere('day', $dayStr);
            $result[] = [
                'label' => $cursor->format('d/M'),
                'count' => $row ? (int) $row->count : 0,
            ];
            $cursor->addDay();
        }

        return $result;
    }

    /**
     * Obtiene la distribución de citas por servicio.
     *
     * @param int $days Número de días hacia atrás para analizar (por defecto 30).
     * @return array Array de servicios con nombre, cantidad total de citas y citas completadas.
     */
    public function getDistributionByService(int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('appointments')
            ->join('appointment_services', 'appointments.service_id', '=', 'appointment_services.id')
            ->whereBetween('appointments.appointment_date', [$startDate, Carbon::now()])
            ->selectRaw('appointment_services.name as service_name, COUNT(*) as count, SUM(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) as completed')
            ->groupBy('appointment_services.id', 'appointment_services.name')
            ->orderByDesc('count')
            ->get();

        return $rows->map(function ($r) {
            return [
                'service' => $r->service_name, 
                'count' => (int) $r->count,
                'completed' => (int) $r->completed
            ];
        })->toArray();
    }

    /**
     * Obtiene las horas pico de creación de citas en un día.
     *
     * @param int $days Número de días hacia atrás para analizar (por defecto 7).
     * @return array Array de 24 horas con conteo de citas creadas.
     */
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

    /**
     * Obtiene los días de la semana con mayor cantidad de citas.
     *
     * @param int $days Número de días hacia atrás para analizar (por defecto 30).
     * @return array Array de días con nombre en español, índice numérico y conteo.
     */
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

    /**
     * Obtiene la tendencia de cancelaciones por semana.
     *
     * @param int $weeks Número de semanas hacia atrás para analizar (por defecto 8).
     * @return array Array semanal con total de citas, cancelaciones y tasa de cancelación.
     */
    public function getCancellationTrend(int $weeks = 8): array
    {
        $startDate = Carbon::now()->copy()->subWeeks($weeks);

        $rows = DB::table('appointments')
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('YEARWEEK(created_at) as year_week, COUNT(*) as total, SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        $result = [];
        $rows->each(function ($r) use (&$result) {
            $result[] = [
                'label' => 'W' . $r->year_week,
                'total' => (int) $r->total,
                'cancelled' => (int) $r->cancelled,
                'rate' => $r->total > 0 ? round(($r->cancelled / $r->total) * 100, 1) : 0,
            ];
        });

        return $result;
    }

    /**
     * Obtiene la tendencia de no-show por semana.
     *
     * @param int $weeks Número de semanas hacia atrás para analizar (por defecto 8).
     * @return array Array semanal con total de citas, no-shows y tasa de no-show.
     */
    public function getNoShowTrend(int $weeks = 8): array
    {
        $startDate = Carbon::now()->copy()->subWeeks($weeks);

        $rows = DB::table('appointments')
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('YEARWEEK(created_at) as year_week, COUNT(*) as total, SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_show')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        $result = [];
        $rows->each(function ($r) use (&$result) {
            $result[] = [
                'label' => 'W' . $r->year_week,
                'total' => (int) $r->total,
                'no_show' => (int) $r->no_show,
                'rate' => $r->total > 0 ? round(($r->no_show / $r->total) * 100, 1) : 0,
            ];
        });

        return $result;
    }

    /**
     * Obtiene la tasa de retorno de visitantes.
     *
     * Calcula cuántos visitantes han vuelto a agendar citas dentro del periodo.
     *
     * @param int $days Número de días hacia atrás para analizar (por defecto 90).
     * @return array Total de visitantes, visitantes recurrentes y tasa de retorno.
     */
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

    /**
     * Obtiene estadísticas de citas filtradas por equipo.
     *
     * @param int $teamId Identificador del equipo.
     * @param int $days Número de días hacia atrás para analizar (por defecto 30).
     * @return array Total, conteos por estado y tasas de no-show y cancelación por equipo.
     */
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
