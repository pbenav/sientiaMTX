<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\ExecutiveMetricsService;
use App\Services\Metrics\SurveyMetricsService;
use App\Services\Metrics\GamificationMetricsService;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    /**
     * Executive dashboard.
     */
    public function index(Request $request)
    {
        $days = $request->input('days', 30);

        $executive = app(ExecutiveMetricsService::class);
        $survey = app(SurveyMetricsService::class);
        $gamification = app(GamificationMetricsService::class);

        $orgHealth = $executive->orgHealth($days);
        $teamComparison = $executive->compareTeams($days);
        $criticalAlerts = $executive->criticalAlerts($days);
        $retentionRisk = $executive->retentionRisk($days);
        $wellnessDist = $executive->wellnessDistribution($days);
        $satisfactionTrend = $executive->satisfactionTrend($days);
        $wellnessROI = $executive->wellnessROI($days);

        $orgEngagement = $gamification->getEngagementScore(1, $days);

        $activitySummary = [
            'total_activities' => \DB::table('activities')->where('updated_at', '>=', now()->subDays(7))->count(),
            'completed' => \DB::table('activities')->where('updated_at', '>=', now()->subDays(7))->whereJsonContains('status', 'completed')->count(),
            'total_users' => \DB::table('users')->whereNotNull('favorite_team_id')->distinct()->count(),
            'active_users' => \DB::table('activities')->where('updated_at', '>=', now()->subDays(7))->whereNotNull('created_by_id')->distinct('created_by_id')->count('created_by_id'),
        ];

        $recentSurveys = \DB::table('surveys')
            ->where('created_at', '>=', now()->subDays(90))
            ->select('id', 'title', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $kudosSummary = [
            'total' => \DB::table('kudos')->where('created_at', '>=', now()->subDays(30))->count(),
            'unique_givers' => \DB::table('kudos')->where('created_at', '>=', now()->subDays(30))->distinct('from_user_id')->count('from_user_id'),
            'unique_receivers' => \DB::table('kudos')->where('created_at', '>=', now()->subDays(30))->distinct('to_user_id')->count('to_user_id'),
        ];

        return view('metrics.executive.dashboard', compact(
            'orgHealth', 'teamComparison', 'criticalAlerts', 'retentionRisk',
            'wellnessDist', 'satisfactionTrend', 'wellnessROI',
            'orgEngagement', 'activitySummary',
            'recentSurveys', 'kudosSummary', 'days'
        ));
    }
}
