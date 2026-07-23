<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de métricas de productividad individual y de equipo.
 *
 * Proporciona puntuaciones de productividad basadas en tasa de
 * completado, entrega a tiempo, rachas de productividad, tendencias,
 * actividades bloqueadas, precisión de estimaciones y horarios
 * pico de productividad.
 */
class ProductivityMetricsService
{
    /**
     * Obtiene la puntuación general de productividad de un usuario.
     *
     * Combina tasa de completado, entrega a tiempo, racha, tendencia
     * y penalización por actividades bloqueadas con ponderaciones específicas.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return array Puntuación de productividad y sus componentes desglosados.
     */
    public function getProductivityScore(int $userId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $completionRate = $this->getCompletionRate($userId, $days);
        $onTimeDelivery = $this->getOnTimeDelivery($userId, $days);
        $streak = $this->getProductivityStreak($userId);
        $trend = $this->getProductivityTrend($userId, $days);
        $blockedCount = count($this->getBlockedActivities($userId));

        $streakNorm = min(100, $streak * 5);
        $trendNorm = $trend === 'improving' ? 100 : ($trend === 'stable' ? 60 : 30);
        $blockedPenalty = min(30, $blockedCount * 5);

        $score = round(
            ($completionRate * 0.35) +
            ($onTimeDelivery * 0.25) +
            ($streakNorm * 0.20) +
            ($trendNorm * 0.10) +
            ($blockedPenalty * -1),
            2
        );

        return [
            'score' => max(0, $score),
            'completion_rate' => round($completionRate, 2),
            'on_time_delivery' => round($onTimeDelivery, 2),
            'streak_days' => $streak,
            'blocked_activities' => $blockedCount,
            'trend' => $trend,
        ];
    }

    /**
     * Obtiene la tasa de finalización de actividades de un usuario.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return float Tasa de completado en porcentaje (0-100).
     */
    public function getCompletionRate(int $userId, ?int $days = 7): float
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $stats = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->whereJsonDoesntContain('status->value', ['archived', 'trashed'])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN JSON_EXTRACT(status, '$.value') = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->first();

        $total = $stats->total ?? 0;
        if ($total === 0) return 0;

        return round(($stats->completed / $total) * 100, 2);
    }

    /**
     * Obtiene la tasa de finalización desglosada por prioridad.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return array Array de prioridades con total, completadas y tasa.
     */
    public function getCompletionRateByPriority(int $userId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->whereJsonDoesntContain('status->value', ['archived', 'trashed'])
            ->selectRaw("
                priority,
                COUNT(*) as total,
                SUM(CASE WHEN JSON_EXTRACT(status, '$.value') = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->groupBy('priority')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->get();

        return $rows->map(function ($row) {
            return [
                'priority' => $row->priority,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
                'rate' => $row->total > 0 ? round(($row->completed / $row->total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Obtiene la tasa de entrega a tiempo de un usuario.
     *
     * Calcula el porcentaje de actividades completadas antes o en la fecha límite.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return float Tasa de entrega a tiempo en porcentaje (0-100).
     */
    public function getOnTimeDelivery(int $userId, ?int $days = 7): float
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $stats = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->whereNotNull('due_date')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN due_date >= updated_at THEN 1 ELSE 0 END) as on_time
            ")
            ->first();

        $total = $stats->total ?? 0;
        if ($total === 0) return 100;

        return round(($stats->on_time / $total) * 100, 2);
    }

    /**
     * Obtiene las actividades vencidas de un usuario.
     *
     * Incluye actividades pendientes o en progreso cuya fecha límite ya pasó,
     * con cálculo de días de retraso. Limitado a 20 resultados.
     *
     * @param int $userId Identificador del usuario.
     * @return array Array de actividades vencidas con días de retraso calculados.
     */
    public function getOverdueActivities(int $userId): array
    {
        return DB::table('activities')
            ->where('created_by_id', $userId)
            ->where('due_date', '<', Carbon::now())
            ->where(function ($q) {
                $q->whereJsonContains('status', 'pending')
                  ->orWhereJsonContains('status', 'in_progress');
            })
            ->select('id', 'title', 'status', 'priority', 'due_date', 'updated_at')
            ->orderBy('due_date')
            ->limit(20)
            ->get()
            ->map(function ($a) {
                $a->days_overdue = max(0, Carbon::now()->diffInDays(Carbon::parse($a->due_date ?? $a->updated_at), false) * -1);
                return $a;
            })->toArray();
    }

    /**
     * Obtiene la precisión de las estimaciones de un usuario.
     *
     * Se basa en el promedio de progreso de actividades completadas con fecha límite
     * como proxy de la capacidad de estimación.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 14).
     * @return float Puntuación de precisión de estimación (0-100).
     */
    public function getEstimationAccuracy(int $userId, ?int $days = 14): float
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $stats = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->selectRaw("
                COUNT(*) as total,
                AVG(COALESCE(progress_percentage, 0)) as avg_progress
            ")
            ->first();

        $total = $stats->total ?? 0;
        if ($total === 0) return 100;

        return round(min(100, ($stats->avg_progress ?? 0) * 1.5), 2);
    }

    /**
     * Obtiene la racha actual de productividad de un usuario.
     *
     * Cuenta días consecutivos con al menos una actividad completada,
     * desde hoy hacia atrás, hasta un máximo de 90 días.
     *
     * @param int $userId Identificador del usuario.
     * @return int Número de días consecutivos con actividad completada.
     */
    public function getProductivityStreak(int $userId): int
    {
        $startDate = Carbon::now()->copy()->subDays(90);

        $dailyCounts = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->where('updated_at', '>=', $startDate)
            ->selectRaw('DATE(updated_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->pluck('count', 'day');

        $streak = 0;
        $cursor = Carbon::today();

        while (true) {
            $dayStr = $cursor->toDateString();
            $count = $dailyCounts->get($dayStr, 0);
            if ($count > 0) {
                $streak++;
                $cursor->subDay();
            } else {
                break;
            }
            if ($streak > 90) break;
        }

        return $streak;
    }

    /**
     * Determina la tendencia de productividad de un usuario.
     *
     * Compara la cantidad de actividades completadas en la segunda mitad
     * del periodo con la primera mitad. Devuelve 'improving', 'stable' o 'declining'.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return string Tendencia: 'improving', 'stable' o 'declining'.
     */
    public function getProductivityTrend(int $userId, ?int $days = 7): string
    {
        $half = (int) ($days / 2);
        $recentStart = Carbon::now()->copy()->subDays($half);
        $olderStart = Carbon::now()->copy()->subDays($days);

        $recentCompleted = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->whereBetween('updated_at', [$recentStart, Carbon::now()])
            ->count();

        $olderCompleted = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->whereBetween('updated_at', [$olderStart, $recentStart])
            ->count();

        if ($olderCompleted === 0) return $recentCompleted > 0 ? 'improving' : 'stable';

        $change = (($recentCompleted - $olderCompleted) / $olderCompleted) * 100;
        if ($change > 10) return 'improving';
        if ($change < -10) return 'declining';
        return 'stable';
    }

    /**
     * Obtiene las actividades bloqueadas de un usuario.
     *
     * Incluye actividades con estado 'blocked' o en progreso vencidas.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return array Array de actividades bloqueadas con días de bloqueo calculados.
     */
    public function getBlockedActivities(int $userId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        return DB::table('activities')
            ->where('created_by_id', $userId)
            ->where(function ($q) use ($startDate) {
                $q->whereJsonContains('status->value', 'blocked')
                  ->orWhere(function ($q2) {
                      $q2->whereJsonContains('status->value', 'in_progress')
                         ->where('due_date', '<', Carbon::now());
                  });
            })
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->select('id', 'title', 'status', 'priority', 'due_date', 'updated_at', 'metadata')
            ->orderBy('due_date')
            ->limit(20)
            ->get()
            ->map(function ($a) {
                $a->blocked_days = max(0, Carbon::now()->diffInDays(Carbon::parse($a->due_date ?? $a->updated_at), false) * -1);
                return $a;
            })->toArray();
    }

    /**
     * Obtiene la capacidad de respuesta de un usuario ante nudges.
     *
     * Calcula el porcentaje de actividades creadas que fueron completadas
     * dentro del periodo, como indicador de proactividad.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 14).
     * @return float Porcentaje de actividades creadas que fueron completadas.
     */
    public function getNudgeResponsiveness(int $userId, ?int $days = 14): float
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $recentCompleted = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->count();

        $recentCreated = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereBetween('created_at', [$startDate, Carbon::now()])
            ->count();

        if ($recentCreated === 0) return 100;

        return round(min(100, ($recentCompleted / $recentCreated) * 100), 2);
    }

    /**
     * Obtiene la distribución de actividades por tipo de un usuario.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return array Array de tipos de actividad con conteo.
     */
    public function getActivityDistribution(int $userId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        return DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereBetween('updated_at', [$startDate, Carbon::now()])
            ->whereJsonDoesntContain('status->value', ['archived', 'trashed'])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Obtiene las horas pico de productividad de un usuario.
     *
     * Basado en registros de tiempo, identifica las 5 horas con mayor
     * cantidad de actividades completadas.
     *
     * @param int $userId Identificador del usuario.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 14).
     * @return array Array de horas con conteo de completados.
     */
    public function getPeakProductivityHours(int $userId, ?int $days = 14): array
    {
        return DB::table('time_logs')
            ->where('user_id', $userId)
            ->where('start_at', '>=', Carbon::now()->copy()->subDays($days))
            ->selectRaw('HOUR(start_at) as hour, COUNT(*) as completions')
            ->groupBy('hour')
            ->orderByDesc('completions')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Obtiene la puntuación de productividad de un equipo.
     *
     * Calcula la tasa de completado global del equipo filtrando por equipo favorito
     * de los usuarios.
     *
     * @param int $teamId Identificador del equipo.
     * @param int|null $days Número de días hacia atrás para analizar (por defecto 7).
     * @return array Tasa de completado, total de actividades y actividades completadas.
     */
    public function getTeamProductivity(int $teamId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $stats = DB::table('activities')
            ->join('users', 'activities.created_by_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereJsonDoesntContain('activities.status->value', ['archived', 'trashed'])
            ->whereBetween('activities.updated_at', [$startDate, Carbon::now()])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN JSON_EXTRACT(activities.status, '$.value') = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->first();

        $total = $stats->total ?? 0;
        if ($total === 0) return ['completion_rate' => 0, 'total' => 0, 'completed' => 0];

        return [
            'completion_rate' => round(($stats->completed / $total) * 100, 2),
            'total' => (int) $total,
            'completed' => (int) $stats->completed,
        ];
    }
}
