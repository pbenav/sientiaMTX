<?php

namespace App\Services\Metrics;

use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExecutiveMetricsService
{
    /**
     * Get organizational health summary.
     */
    public function orgHealth(int $days = 30): array
    {
        // Org-wide wellness
        $wellness = app(WellnessMetricsService::class);
        $orgWellness = $wellness->getWellnessScore(null, $days);

        // Org-wide productivity
        $productivity = app(ProductivityMetricsService::class);
        $orgProductivity = $productivity->getProductivityScore(null, $days);

        // Org-wide engagement
        $engagement = app(GamificationMetricsService::class);
        $orgEngagement = $engagement->getEngagementScore(null, $days);

        // Get teams
        $teams = DB::table('teams')->get();

        $teamHealth = [];
        $teams->each(function ($team) use (&$teamHealth, $days) {
            $teamModel = \App\Models\Team::find($team->id);
            if ($teamModel) {
                $teamHealth[] = [
                    'id' => $team->id,
                    'name' => $team->name,
                    'wellness' => $wellness->getTeamWellness($team->id, $days),
                    'productivity' => $productivity->getTeamProductivity($team->id, $days),
                    'engagement' => $engagement->getTeamEngagement($team->id, $days),
                ];
            }
        });

        return [
            'wellness' => $orgWellness,
            'productivity' => $orgProductivity,
            'engagement' => $orgEngagement,
            'team_count' => $teams->count(),
            'teams' => $teamHealth,
            'days_analyzed' => $days,
        ];
    }

    /**
     * Compare teams health.
     */
    public function compareTeams(int $days = 30): array
    {
        $teams = DB::table('teams')->get();

        $wellness = app(WellnessMetricsService::class);
        $productivity = app(ProductivityMetricsService::class);
        $engagement = app(GamificationMetricsService::class);
        $teamService = app(TeamMetricsService::class);

        $comparison = [];

        $teams->each(function ($team) use (&$comparison, $days, $wellness, $productivity, $engagement, $teamService) {
            $teamModel = \App\Models\Team::find($team->id);
            if (!$teamModel) return;

            $wellnessData = $wellness->getTeamWellness($team->id, $days);
            $productivityData = $productivity->getTeamProductivity($team->id, $days);
            $engagementData = $engagement->getTeamEngagement($team->id, $days);

            $comparison[] = [
                'id' => $team->id,
                'name' => $team->name,
                'member_count' => DB::table('users')->where('favorite_team_id', $team->id)->count(),
                'wellness_score' => $wellnessData['avg_wellness'] ?? 0,
                'productivity_score' => $productivityData['completion_rate'] ?? 0,
                'engagement_score' => $engagementData['score'] ?? 0,
                'velocity' => $teamService->getTeamVelocity($team->id, $days),
            ];
        });

        // Rank by overall score
        $comparison = collect($comparison)->map(function ($team) {
            $team['overall'] = round(($team['wellness_score'] + $team['productivity_score'] + $team['engagement_score']) / 3, 1);
            return $team;
        })->sortByDesc('overall')->values()->toArray();

        return $comparison;
    }

    /**
     * Get critical alerts for executives.
     */
    public function criticalAlerts(int $days = 7): array
    {
        // High burnout risk users
        $burnoutUsers = DB::table('users')
            ->whereNotNull('favorite_team_id')
            ->select('id', 'name', 'favorite_team_id')
            ->get();

        $highRisk = [];
        $burnoutUsers->each(function ($user) use (&$highRisk) {
            $wellness = app(WellnessMetricsService::class);
            $risk = $wellness->getBurnoutRisk($user->id, 30);
            if ($risk['level'] === 'high' || $risk['level'] === 'critical') {
                $highRisk[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'team_id' => $user->favorite_team_id,
                    'risk_score' => $risk['score'],
                    'risk_level' => $risk['level'],
                ];
            }
        });

        // Teams with high no-show rates
        $noShowTeams = DB::table('users')
            ->select('favorite_team_id')
            ->whereNotNull('favorite_team_id')
            ->distinct()
            ->get();

        $highNoShow = [];
        $noShowTeams->each(function ($team) use (&$highNoShow) {
            $teamModel = \App\Models\Team::find($team->favorite_team_id);
            if (!$teamModel) return;

            $appointments = app(AppointmentMetricsService::class);
            $stats = $appointments->getAppointmentStats($team->favorite_team_id, 30);
            if ($stats['no_show_rate'] > 20) {
                $highNoShow[] = [
                    'team_id' => $team->favorite_team_id,
                    'no_show_rate' => $stats['no_show_rate'],
                ];
            }
        });

        // Teams with declining productivity
        $decliningTeams = [];
        $teams = DB::table('teams')->get();
        $productivity = app(ProductivityMetricsService::class);

        $teams->each(function ($team) use (&$decliningTeams, $productivity) {
            $teamModel = \App\Models\Team::find($team->id);
            if (!$teamModel) return;

            $recent = $productivity->getTeamProductivity($team->id, 7);
            $previous = $productivity->getTeamProductivity($team->id, 14);

            if ($recent['completion_rate'] < $previous['completion_rate'] - 10) {
                $decliningTeams[] = [
                    'team_id' => $team->id,
                    'name' => $team->name,
                    'recent_score' => $recent['completion_rate'],
                    'previous_score' => $previous['completion_rate'],
                    'decline' => round($previous['completion_rate'] - $recent['completion_rate'], 1),
                ];
            }
        });

        return [
            'burnout_risk_users' => $highRisk,
            'high_no_show_teams' => $highNoShow,
            'declining_productivity_teams' => $decliningTeams,
            'total_alerts' => count($highRisk) + count($highNoShow) + count($decliningTeams),
        ];
    }

    /**
     * Get retention risk analysis.
     */
    public function retentionRisk(int $days = 90): array
    {
        $users = DB::table('users')
            ->whereNotNull('favorite_team_id')
            ->select('id', 'name', 'favorite_team_id', 'created_at')
            ->get();

        $riskFactors = [];
        $wellness = app(WellnessMetricsService::class);
        $productivity = app(ProductivityMetricsService::class);

        $users->each(function ($user) use (&$riskFactors, $wellness, $productivity, $days) {
            $factors = [];
            $riskScore = 0;

            // Check burnout
            $burnoutRisk = $wellness->getBurnoutRisk($user->id, 30);
            if ($burnoutRisk['score'] > 50) {
                $factors[] = 'high_burnout_risk';
                $riskScore += 30;
            }

            // Check declining productivity
            $prod = $productivity->getProductivityScore($user->id, 7);
            $prodPrev = $productivity->getProductivityScore($user->id, 14);
            if ($prod['score'] < $prodPrev['score'] - 15) {
                $factors[] = 'declining_productivity';
                $riskScore += 20;
            }

            // Check inactivity
            $recentActivity = DB::table('activities')
                ->where('created_by_id', $user->id)
                ->where('updated_at', '>=', now()->subDays(14))
                ->count();

            if ($recentActivity === 0) {
                $factors[] = 'inactive';
                $riskScore += 40;
            }

            if ($riskScore > 30) {
                $riskFactors[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'team_id' => $user->favorite_team_id,
                    'risk_score' => min(100, $riskScore),
                    'factors' => $factors,
                ];
            }
        });

        usort($riskFactors, function ($a, $b) {
            return $b['risk_score'] <=> $a['risk_score'];
        });

        return [
            'high_risk_users' => array_slice($riskFactors, 0, 20),
            'total_at_risk' => count($riskFactors),
        ];
    }

    /**
     * Get organizational wellness distribution.
     */
    public function wellnessDistribution(int $days = 30): array
    {
        $users = DB::table('users')
            ->select('id', 'name', 'favorite_team_id')
            ->get();

        $wellness = app(WellnessMetricsService::class);
        $scores = [];

        $users->each(function ($user) use (&$scores, $wellness) {
            $score = $wellness->getWellnessScore($user->id, $days);
            $scores[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'score' => $score['wellness_score'],
            ];
        });

        $distribution = [
            'excellent' => collect($scores)->where('score', '>=', 80)->count(),
            'good' => collect($scores)->whereBetween('score', [60, 79])->count(),
            'average' => collect($scores)->whereBetween('score', [40, 59])->count(),
            'poor' => collect($scores)->where('score', '<', 40)->count(),
        ];

        return [
            'distribution' => $distribution,
            'average_score' => $scores ? round(collect($scores)->avg('score'), 1) : 0,
            'total_users' => count($scores),
        ];
    }

    /**
     * Get satisfaction trends.
     */
    public function satisfactionTrend(int $days = 180): array
    {
        $survey = app(SurveyMetricsService::class);
        $trend = $survey->getSatisfactionTrend($days);

        $nps = $survey->calculateNPS($days);

        return [
            'satisfaction_trend' => $trend,
            'nps' => $nps,
        ];
    }

    /**
     * Calculate ROI of wellness initiatives (simplified).
     */
    public function wellnessROI(int $days = 90): array
    {
        // Simplified ROI: compare productivity before and after wellness initiatives
        // In production, this would use actual financial data

        $productivity = app(ProductivityMetricsService::class);
        $wellness = app(WellnessMetricsService::class);

        $recentProd = $productivity->getProductivityScore(null, 30);
        $previousProd = $productivity->getProductivityScore(null, 60);

        $recentWellness = $wellness->getWellnessScore(null, 30);
        $previousWellness = $wellness->getWellnessScore(null, 60);

        $prodChange = $previousProd['score'] > 0
            ? (($recentProd['score'] - $previousProd['score']) / $previousProd['score']) * 100
            : 0;

        $wellnessChange = $previousWellness['score'] > 0
            ? (($recentWellness['score'] - $previousWellness['score']) / $previousWellness['score']) * 100
            : 0;

        return [
            'productivity_change' => round($prodChange, 1),
            'wellness_change' => round($wellnessChange, 1),
            'correlation' => $prodChange > 0 && $wellnessChange > 0 ? 'positive' : ($prodChange < 0 && $wellnessChange < 0 ? 'positive' : 'neutral'),
        ];
    }
}
