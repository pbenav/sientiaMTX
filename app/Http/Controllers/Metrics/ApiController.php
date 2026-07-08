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

class ApiController extends Controller
{
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

    public function leaderboard(Request $request)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period', 'weekly');

        $gamification = app(GamificationMetricsService::class);

        return response()->json($gamification->getLeaderboard($teamId, 20, $period));
    }

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

    public function snapshots(Request $request)
    {
        return response()->json(\App\Models\MetricSnapshot::latest()->limit(50)->get());
    }

    public function alerts(Request $request)
    {
        return response()->json(\App\Models\MetricAlert::where('resolved_at', null)->latest()->limit(20)->get());
    }
}
