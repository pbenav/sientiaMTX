<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Services\Metrics\GamificationMetricsService;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerDashboardController extends Controller
{
    /**
     * Manager team dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = $request->input('team_id', $user->favorite_team_id);

        $team = Team::find($teamId);
        if (!$team) {
            return redirect()->route('metrics.personal.daily');
        }

        $wellness = app(WellnessMetricsService::class);
        $productivity = app(ProductivityMetricsService::class);
        $teamService = app(TeamMetricsService::class);
        $gamification = app(GamificationMetricsService::class);

        $days = $request->input('days', 7);

        $velocity = $teamService->getTeamVelocity($team->id, $days);
        $workload = $teamService->getLoadDistribution($team->id);
        $bottlenecks = $teamService->getBottlenecks($team->id, 5);
        $engagement = $teamService->getTeamEngagement($team->id, $days);
        $collaboration = $teamService->getCollaborationIndex($team->id, $days);
        $completionByMember = $teamService->getCompletionByMember($team->id, $days);

        $teamMembers = \DB::table('users')->where('favorite_team_id', $team->id)->get();
        $wellnessRadar = [];
        $teamMembers->each(function ($member) use (&$wellnessRadar, $wellness) {
            $score = $wellness->getWellnessScore($member->id, 7);
            $wellnessRadar[] = [
                'id' => $member->id,
                'name' => $member->name,
                'score' => $score['wellness_score'],
                'level' => $this->scoreLevel($score['wellness_score']),
            ];
        });

        $weekStart = now()->startOfWeek();
        $teamUserIds = \DB::table('users')->where('favorite_team_id', $team->id)->pluck('id');

        $totalTasks = \DB::table('activities')
            ->whereIn('created_by_id', $teamUserIds)
            ->where('updated_at', '>=', $weekStart)
            ->count();

        $completedTasks = \DB::table('activities')
            ->whereIn('created_by_id', $teamUserIds)
            ->where('updated_at', '>=', $weekStart)
            ->whereJsonContains('status', 'completed')
            ->count();

        $sprintProgress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        $recentKudos = \DB::table('kudos')
            ->join('users as senders', 'kudos.from_user_id', '=', 'senders.id')
            ->join('users as receivers', 'kudos.to_user_id', '=', 'receivers.id')
            ->whereIn('senders.favorite_team_id', [$team->id])
            ->orWhereIn('receivers.favorite_team_id', [$team->id])
            ->select('kudos.*', 'senders.name as sender_name', 'receivers.name as receiver_name')
            ->orderBy('kudos.created_at', 'desc')
            ->limit(10)
            ->get();

        $alerts = \Schema::hasTable('metric_alerts')
            ? \DB::table('metric_alerts')
                ->where(function ($q) use ($team) {
                    $q->where('team_id', $team->id);
                })
                ->where('is_read', false)
                ->where('resolved_at', null)
                ->where('severity', '!=', 'info')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
            : collect();

        $teamSummary = [
            'team_size' => $teamMembers->count(),
            'avg_productivity' => 0,
            'avg_wellness' => $wellnessRadar->avg('score') ?? 0,
        ];

        return view('metrics.manager.dashboard', compact(
            'team', 'teamSummary', 'velocity', 'workload', 'bottlenecks',
            'engagement', 'collaboration', 'completionByMember', 'wellnessRadar',
            'sprintProgress', 'recentKudos', 'alerts', 'days'
        ));
    }

    private function scoreLevel(float $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        return 'poor';
    }
}
