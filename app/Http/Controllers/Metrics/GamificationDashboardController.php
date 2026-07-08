<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\GamificationMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GamificationDashboardController extends Controller
{
    /**
     * Gamification dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = $request->input('team_id', $user->favorite_team_id);

        $team = Team::find($teamId);
        $gamification = app(GamificationMetricsService::class);

        $period = $request->input('period', 'week');

        $gamificationData = $gamification->getUserGamification($user->id);
        $points = $gamificationData['points'] ?? 0;
        $streak = $gamificationData['streak'] ?? 0;
        $badges = $gamificationData['badges'] ?? [];

        $leaderboard = $gamification->getLeaderboard($team?->id, 20, $period);
        $engagement = $gamification->getEngagementScore($user->id, 30);
        $badgeDistribution = $team ? $gamification->getBadgeDistribution($team->id, 30) : [];
        $kudosDistribution = $team ? $gamification->getKudosDistribution($team->id, 30) : [];
        $streakLeaderboard = $team ? $gamification->getStreakLeaderboard($team->id, 10) : [];
        $recentAchievements = $gamification->getRecentAchievements($user->id, 10);

        $engagementTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $activities = \DB::table('activities')
                ->where('created_by_id', $user->id)
                ->whereDate('updated_at', $date)
                ->whereJsonContains('status', 'completed')
                ->count();
            $kudos = \DB::table('kudos')
                ->where('from_user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count();
            $engagementTrend[] = [
                'date' => $date->format('Y-m-d'),
                'activities' => $activities,
                'kudos' => $kudos,
            ];
        }

        return view('metrics.gamification.dashboard', compact(
            'user', 'team', 'points', 'leaderboard', 'badges', 'streak',
            'engagement', 'badgeDistribution', 'kudosDistribution',
            'streakLeaderboard', 'engagementTrend', 'recentAchievements', 'period'
        ));
    }
}
