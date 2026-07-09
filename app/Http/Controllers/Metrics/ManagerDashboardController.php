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

        $days = $request->input('days', 90); // Default a 90 días para mostrar histórico
        $weekStart = now()->subDays($days);
        
        $teamMembers = $team->members;
        $teamUserIds = $teamMembers->pluck('id')->toArray();
        if (empty($teamUserIds)) {
            $teamUserIds = [-1]; // Fallback
        }

        $totalTasks = \App\Models\Activity::whereIn('created_by_id', $teamUserIds)
            ->where('updated_at', '>=', $weekStart)
            ->count();
        $completedTasks = \App\Models\Activity::whereIn('created_by_id', $teamUserIds)
            ->where('updated_at', '>=', $weekStart)
            ->whereIn('status->value', ['completed', 'done', 'approved', 'triggered'])
            ->count();
            
        $sprintProgress = [
            'progress' => $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0,
            'total' => $totalTasks,
            'completed' => $completedTasks,
        ];

        // Velocity history (last 8 weeks)
        $velocityHistory = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            $count = \App\Models\Activity::whereIn('created_by_id', $teamUserIds)
                ->whereBetween('updated_at', [$start, $end])
                ->whereIn('status->value', ['completed', 'done', 'approved', 'triggered'])
                ->count();
            $velocityHistory[] = ['week' => 'W' . $start->format('W'), 'count' => $count];
        }

        $velocity = count($velocityHistory) > 0 ? array_sum(array_column($velocityHistory, 'count')) / count($velocityHistory) : 0;

        // Member completion rates
        $memberCompletionRates = [];
        $overloadedMembers = [];
        $underloadedMembers = [];
        
        foreach ($teamMembers as $member) {
            $memberTotal = \App\Models\Activity::where('created_by_id', $member->id)
                ->where('updated_at', '>=', $weekStart)->count();
            $memberCompleted = \App\Models\Activity::where('created_by_id', $member->id)
                ->where('updated_at', '>=', $weekStart)
                ->whereIn('status->value', ['completed', 'done', 'approved', 'triggered'])->count();
            
            $rate = $memberTotal > 0 ? ($memberCompleted / $memberTotal) * 100 : 0;
            $memberCompletionRates[] = [
                'name' => $member->name,
                'completed' => $memberCompleted,
                'completion_rate' => $rate,
            ];

            // simple logic for workload
            $pendingCount = $memberTotal - $memberCompleted;
            $workload = min(100, $pendingCount * 10);
            if ($workload > 80) {
                $overloadedMembers[] = ['profile_photo' => $member->profile_photo_url, 'name' => $member->name, 'workload' => $workload];
            } elseif ($workload < 30) {
                $underloadedMembers[] = ['profile_photo' => $member->profile_photo_url, 'name' => $member->name, 'workload' => $workload];
            }
        }

        $teamMetricsData = [
            'completed_this_week' => $completedTasks,
            'completion_rate' => $sprintProgress['progress'],
            'velocity' => $velocity,
            'at_risk_count' => count($overloadedMembers),
            'velocity_history' => $velocityHistory,
            'member_completion_rates' => $memberCompletionRates,
            'priority_completion_data' => [], // Dynamic below
            'collaboration_index' => 0, // Dynamic below
        ];

        // Bottlenecks
        $bottlenecksRaw = \App\Models\Activity::with(['assignedTo'])
            ->whereIn('created_by_id', $teamUserIds)
            ->whereNotIn('status->value', ['completed', 'done', 'approved', 'triggered', 'cancelled'])
            ->where('updated_at', '<', now()->subDays(3))
            ->get();
        $bottlenecks = [];
        foreach ($bottlenecksRaw as $activity) {
            $bottlenecks[] = [
                'activity' => $activity,
                'days_stuck' => (int) abs(now()->diffInDays($activity->updated_at)),
            ];
        }

        // Kudos Board
        $kudosBoard = \App\Models\Kudo::with(['sender', 'receiver'])
            ->whereIn('to_user_id', $teamUserIds)
            ->orWhereIn('from_user_id', $teamUserIds)
            ->orderBy('created_at', 'desc')
            ->limit(9)
            ->get();

        // Quadrant
        $activitiesForMatrix = \App\Models\Activity::whereIn('created_by_id', $teamUserIds)
            ->whereIn('type', \App\Models\Activity::MATRIX_TYPES)
            ->where('is_archived', false)
            ->get();
            
        $dummy = new \App\Models\Activity(); // Para usar el trait
        
        $qCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        foreach ($activitiesForMatrix as $activity) {
            $q = $dummy->getQuadrant($activity);
            $qCounts[$q]++;
        }

        // FALLBACK LOGIC
        $hasDummyData = false;
        if ($totalTasks === 0) {
            $hasDummyData = true;
            $totalTasks = rand(30, 80);
            $completedTasks = rand(15, $totalTasks - 5);
            $sprintProgress['total'] = $totalTasks;
            $sprintProgress['completed'] = $completedTasks;
            $sprintProgress['progress'] = ($completedTasks / $totalTasks) * 100;
            
            if ($velocity === 0) {
                foreach ($velocityHistory as &$vh) {
                    $vh['count'] = rand(10, 50);
                }
                $velocity = array_sum(array_column($velocityHistory, 'count')) / count($velocityHistory);
                $teamMetricsData['velocity_history'] = $velocityHistory;
                $teamMetricsData['velocity'] = $velocity;
            }
            
            if (empty($memberCompletionRates) || collect($memberCompletionRates)->sum('completed') === 0) {
                $memberCompletionRates = [];
                $overloadedMembers = [];
                $underloadedMembers = [];
                foreach ($teamMembers as $member) {
                    $mTotal = rand(5, 20);
                    $mComp = rand(2, $mTotal);
                    $rate = ($mComp / $mTotal) * 100;
                    $memberCompletionRates[] = [
                        'name' => $member->name,
                        'completed' => $mComp,
                        'completion_rate' => $rate
                    ];
                    $workload = ($mTotal - $mComp) * 10;
                    if ($workload > 80) $overloadedMembers[] = ['profile_photo' => $member->profile_photo_url, 'name' => $member->name, 'workload' => min(100, $workload)];
                    elseif ($workload < 30) $underloadedMembers[] = ['profile_photo' => $member->profile_photo_url, 'name' => $member->name, 'workload' => max(0, $workload)];
                }
                $teamMetricsData['member_completion_rates'] = $memberCompletionRates;
                $teamMetricsData['at_risk_count'] = count($overloadedMembers);
            }
            
            if (array_sum($qCounts) === 0) {
                $qCounts = [1 => rand(5, 15), 2 => rand(10, 30), 3 => rand(2, 10), 4 => rand(1, 5)];
            }
            
            $teamMetricsData['completed_this_week'] = $completedTasks;
            $teamMetricsData['completion_rate'] = $sprintProgress['progress'];
        }
        
        $quadrantDistribution = [];
        foreach ([1, 2, 3, 4] as $qId) {
            $meta = $dummy->getQuadrantMetadata($qId);
            $quadrantDistribution[] = [
                'name' => $meta['label'] ?? "Quadrant $qId",
                'activity_count' => $qCounts[$qId],
                'color' => $meta['color'] ?? 'gray',
            ];
        }

        $dynProductivity = $sprintProgress['progress'] > 0 ? $sprintProgress['progress'] : ($hasDummyData ? rand(60, 90) : 0);
        $dynCollaboration = min(100, 40 + (count($kudosBoard) * 5) + (count($teamMembers) * 2));
        $dynWellness = max(0, 100 - (count($overloadedMembers) * 10) - (count($bottlenecks) * 2));
        $dynEngagement = min(100, 50 + ($completedTasks > 0 ? 20 : 0) + (count($kudosBoard) * 2));
        $dynBalance = max(0, 100 - (abs(count($overloadedMembers) - count($underloadedMembers)) * 8) - (count($overloadedMembers) * 5));

        $teamWellness = [
            'team_wellness_score' => $dynWellness,
            'burnout_risk_count' => count($overloadedMembers),
        ];

        $teamProductivity = [
            'productivity_score' => $dynProductivity,
        ];

        $wellnessRadar = [
            'wellness' => $dynWellness,
            'productivity' => $dynProductivity,
            'collaboration' => $dynCollaboration,
            'engagement' => $dynEngagement,
            'balance' => $dynBalance,
        ];

        $teamMetricsData['collaboration_index'] = $dynCollaboration;
        
        $priorityData = [];
        foreach ([1, 2, 3, 4] as $p) {
            $t = \App\Models\Activity::whereIn('created_by_id', $teamUserIds)->where('priority', $p)->where('updated_at', '>=', $weekStart)->count();
            $c = \App\Models\Activity::whereIn('created_by_id', $teamUserIds)->where('priority', $p)->where('updated_at', '>=', $weekStart)->whereIn('status->value', ['completed', 'done', 'approved', 'triggered'])->count();
            $priorityData[] = ['priority' => $p, 'completion' => $t > 0 ? ($c / $t) * 100 : ($hasDummyData ? rand(30, 90) : 0)];
        }
        $teamMetricsData['priority_completion_data'] = $priorityData;

        $alertList = [];
        if (count($bottlenecks) > 0) {
            $alertList[] = ['type' => 'danger', 'message' => count($bottlenecks) . ' actividades atascadas por más de 3 días'];
        }
        if (count($overloadedMembers) > 0) {
            $alertList[] = ['type' => 'warning', 'message' => count($overloadedMembers) . ' miembros del equipo con posible sobrecarga de trabajo'];
        }

        return view('metrics.manager.dashboard', compact(
            'team', 'teamMetricsData', 'bottlenecks', 'wellnessRadar',
            'sprintProgress', 'kudosBoard', 'overloadedMembers', 'underloadedMembers',
            'teamWellness', 'teamProductivity', 'quadrantDistribution', 'alertList', 'days', 'hasDummyData'
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
