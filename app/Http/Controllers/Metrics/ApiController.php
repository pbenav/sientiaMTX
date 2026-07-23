<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\TimeMetricsService;
use App\Services\Metrics\GamificationMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Services\Metrics\AppointmentMetricsService;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API REST para consultar métricas y analíticas de rendimiento del equipo y los usuarios.
 *
 * Expone endpoints JSON para resúmenes personales, tendencias, resúmenes de equipo,
 * bienestar, rankings (leaderboard), citas y vistas ejecutivas. Utiliza servicios de métricas
 * especializados para cada dimensión de análisis.
 *
 * Rutas asociadas (prefix: api/metrics):
 *   - GET /api/metrics/personal-summary
 *   - GET /api/metrics/personal-trends
 *   - GET /api/metrics/team-summary
 *   - GET /api/metrics/team-wellness
 *   - GET /api/metrics/leaderboard
 *   - GET /api/metrics/appointments-summary
 *   - GET /api/metrics/executive-summary
 *   - GET /api/metrics/snapshots
 *   - GET /api/metrics/alerts
 */
class ApiController extends Controller
{
    /**
     * Devuelve un resumen integral de métricas personales del usuario autenticado.
     *
     * Incluye bienestar, productividad, resumen de tiempo y datos de gamificación.
     *
     * @param Request $request Parámetro opcional 'days' para el rango de días (por defecto 7)
     * @return \Illuminate\Http\JsonResponse
     */
    public function personalSummary(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 7);

        $wellness = app(WellnessMetricsService::class);
        $productivity = app(ProductivityMetricsService::class);
        $time = app(TimeMetricsService::class);
        $gamification = app(GamificationMetricsService::class);

        return response()->json([
            'wellness' => $wellness->getWellnessScore($user->id, $days),
            'productivity' => $productivity->getProductivityScore($user->id, $days),
            'time' => $time->getTimeOverview($user->id, $days),
            'gamification' => $gamification->getUserGamification($user->id),
        ]);
    }

    /**
     * Devuelve las tendencias temporales de métricas personales del usuario.
     *
     * Incluye mapa de calor de estado de ánimo y horas diarias trabajadas.
     *
     * @param Request $request Parámetro opcional 'days' para el rango de días (por defecto 30)
     * @return \Illuminate\Http\JsonResponse
     */
    public function personalTrends(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 30);

        $wellness = app(WellnessMetricsService::class);
        $time = app(TimeMetricsService::class);

        return response()->json([
            'wellness' => $wellness->getMoodHeatmap($user->id, $days),
            'time' => $time->getDailyHours($user->id, $days),
        ]);
    }

    /**
     * Devuelve un resumen de métricas de un equipo específico.
     *
     * Incluye velocidad del equipo, distribución de carga, engagement y cuellos de botella.
     *
     * @param Request $request Parámetros 'team_id' (requerido) y 'days' (opcional, por defecto 7)
     * @return \Illuminate\Http\JsonResponse
     */
    public function teamSummary(Request $request)
    {
        $teamId = $request->input('team_id');
        $days = $request->input('days', 7);

        $team = Team::find($teamId);
        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }

        $teamService = app(TeamMetricsService::class);

        return response()->json([
            'velocity' => $teamService->getTeamVelocity($team->id, $days),
            'workload' => $teamService->getLoadDistribution($team->id),
            'engagement' => $teamService->getTeamEngagement($team->id, $days),
            'bottlenecks' => $teamService->getBottlenecks($team->id, 5),
        ]);
    }

    /**
     * Devuelve las métricas de bienestar de un equipo específico.
     *
     * @param Request $request Parámetros 'team_id' (requerido) y 'days' (opcional, por defecto 7)
     * @return \Illuminate\Http\JsonResponse
     */
    public function teamWellness(Request $request)
    {
        $teamId = $request->input('team_id');
        $days = $request->input('days', 7);

        $team = Team::find($teamId);
        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }

        $wellness = app(WellnessMetricsService::class);

        return response()->json($wellness->getTeamWellness($team->id, $days));
    }

    /**
     * Devuelve el ranking (leaderboard) de usuarios de un equipo.
     *
     * @param Request $request Parámetros 'team_id' (requerido) y 'period' (opcional, por defecto 'weekly')
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaderboard(Request $request)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period', 'weekly');

        $gamification = app(GamificationMetricsService::class);

        return response()->json($gamification->getLeaderboard($teamId, 20, $period));
    }

    /**
     * Devuelve un resumen de métricas relacionadas con citas.
     *
     * Incluye estadísticas generales, tendencias de reservas y horas pico de citas.
     *
     * @param Request $request Parámetro opcional 'days' para el rango de días (por defecto 30)
     * @return \Illuminate\Http\JsonResponse
     */
    public function appointmentsSummary(Request $request)
    {
        $days = $request->input('days', 30);

        $appointments = app(AppointmentMetricsService::class);

        return response()->json([
            'stats' => $appointments->getOverview($days),
            'trends' => $appointments->getBookingTrends($days),
            'peakHours' => $appointments->getPeakHours($days),
        ]);
    }

    /**
     * Devuelve un resumen ejecutivo a nivel organizacional.
     *
     * Incluye salud organizacional, comparación entre equipos y alertas críticas.
     *
     * @param Request $request Parámetro opcional 'days' para el rango de días (por defecto 30)
     * @return \Illuminate\Http\JsonResponse
     */
    public function executiveSummary(Request $request)
    {
        $days = $request->input('days', 30);
        $executive = app(\App\Services\Metrics\ExecutiveMetricsService::class);

        return response()->json([
            'orgHealth' => $executive->orgHealth($days),
            'teamComparison' => $executive->compareTeams($days),
            'criticalAlerts' => $executive->criticalAlerts($days),
        ]);
    }

    /**
     * Devuelve los últimos 50 snapshots de métricas almacenados.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function snapshots(Request $request)
    {
        return response()->json(\App\Models\MetricSnapshot::latest()->limit(50)->get());
    }

    /**
     * Devuelve las alertas de métricas que aún no están resueltas.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function alerts(Request $request)
    {
        return response()->json(\App\Models\MetricAlert::where('resolved_at', null)->latest()->limit(20)->get());
    }
}
