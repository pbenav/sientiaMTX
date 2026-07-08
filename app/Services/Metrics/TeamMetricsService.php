<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TeamMetricsService
{
    public function getTeamVelocity(int $teamId, ?int $weeks = 8): array
    {
        $startDate = Carbon::now()->copy()->subWeeks($weeks);

        $rows = DB::table('activities')
            ->join('users', 'activities.created_by_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereJsonContains('activities.status->value', 'completed')
            ->whereBetween('activities.updated_at', [$startDate, Carbon::now()])
            ->selectRaw('YEARWEEK(activities.updated_at) as year_week, SUM(COALESCE(activities.progress_percentage, 0)) as progress')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        $labels = [];
        $data = [];
        $rows->each(function ($r) use (&$labels, &$data) {
            $labels[] = 'W' . $r->year_week;
            $data[] = round((float) $r->progress, 1);
        });

        return ['labels' => $labels, 'data' => $data];
    }

    public function getLoadDistribution(int $teamId): array
    {
        $members = DB::table('users')
            ->where('users.favorite_team_id', $teamId)
            ->select('users.id', 'users.name')
            ->get();

        $data = $members->map(function ($member) use ($teamId) {
            $completed = DB::table('activities')
                ->where('activities.created_by_id', $member->id)
                ->whereJsonContains('activities.status->value', 'completed')
                ->whereBetween('activities.updated_at', [Carbon::now()->copy()->subDays(7), Carbon::now()])
                ->count();

            $inProgress = DB::table('activities')
                ->where('activities.created_by_id', $member->id)
                ->whereJsonContains('activities.status->value', 'in_progress')
                ->count();

            $overdue = DB::table('activities')
                ->where('activities.created_by_id', $member->id)
                ->where('activities.due_date', '<', Carbon::now())
                ->whereNotJson('activities.status->value', ['completed', 'cancelled', 'archived'])
                ->count();

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'overdue' => $overdue,
                'total' => $completed + $inProgress + $overdue,
            ];
        });

        $avg = $data->avg('total') ?? 0;
        $stddev = $data->stdDev('total') ?? 0;
        $thresholdHigh = $avg + 1.5 * $stddev;
        $thresholdLow = max(0, $avg - 1.5 * $stddev);

        $overloaded = $data->filter(fn($m) => $m->total > $thresholdHigh && $avg > 0)->count();
        $underloaded = $data->filter(fn($m) => $m->total < $thresholdLow && $avg > 0)->count();

        return [
            'members' => $data->toArray(),
            'average' => round($avg, 1),
            'stddev' => round($stddev, 1),
            'overloaded_count' => $overloaded,
            'underloaded_count' => $underloaded,
            'coefficient_of_variation' => $avg > 0 ? round(($stddev / $avg) * 100, 1) : 0,
        ];
    }

    public function getBottlenecks(int $teamId, ?int $days = 5): array
    {
        return DB::table('activities')
            ->join('users', 'activities.created_by_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereJsonContains('activities.status->value', 'in_progress')
            ->where('activities.updated_at', '<', Carbon::now()->copy()->subDays($days))
            ->where('activities.is_archived', false)
            ->select(
                'activities.id',
                'activities.title',
                'activities.priority',
                'activities.status',
                'activities.updated_at',
                'users.id as user_id',
                'users.name as assignee'
            )
            ->selectRaw('TIMESTAMPDIFF(DAY, activities.updated_at, NOW()) as days_stuck')
            ->orderByDesc('days_stuck')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->get()
            ->toArray();
    }

    public function getCompletionByMember(int $teamId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('activities')
            ->join('users', 'activities.created_by_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereNotJson('activities.status->value', ['archived', 'trashed'])
            ->whereBetween('activities.updated_at', [$startDate, Carbon::now()])
            ->selectRaw('
                users.id as user_id,
                users.name,
                COUNT(*) as total,
                SUM(CASE WHEN JSON_EXTRACT(activities.status, \'$.value\') = \'completed\' THEN 1 ELSE 0 END) as completed
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('completed')
            ->get();

        return $rows->map(function ($r) {
            return [
                'user_id' => $r->user_id,
                'name' => $r->name,
                'total' => (int) $r->total,
                'completed' => (int) $r->completed,
                'rate' => $r->total > 0 ? round(($r->completed / $r->total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    public function getCollaborationIndex(int $teamId, ?int $days = 30): array
    {
        $crossAssignments = DB::table('activity_assignments')
            ->join('users as assignee', 'activity_assignments.user_id', '=', 'assignee.id')
            ->join('users as assigner', 'activity_assignments.assigned_by_id', '=', 'assigner.id')
            ->where('assignee.favorite_team_id', $teamId)
            ->where('assigner.favorite_team_id', $teamId)
            ->where('assignee.id', '!=', 'assigner.id')
            ->whereBetween('activity_assignments.created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $forumActivity = DB::table('forum_threads')
            ->where('team_id', $teamId)
            ->whereBetween('created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $forumMessages = DB::table('forum_messages')
            ->join('forum_threads', 'forum_messages.forum_thread_id', '=', 'forum_threads.id')
            ->where('forum_threads.team_id', $teamId)
            ->whereBetween('forum_messages.created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $kudosCount = DB::table('kudos')
            ->join('users as sender', 'kudos.from_user_id', '=', 'sender.id')
            ->where('sender.favorite_team_id', $teamId)
            ->whereBetween('kudos.created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $chatMessages = DB::table('chat_messages')
            ->join('chat_groups', 'chat_messages.chat_group_id', '=', 'chat_groups.id')
            ->join('chat_group_user', 'chat_groups.id', '=', 'chat_group_user.chat_group_id')
            ->join('users as chat_user', 'chat_group_user.user_id', '=', 'chat_user.id')
            ->where('chat_user.favorite_team_id', $teamId)
            ->whereBetween('chat_messages.created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $score = min(100, round(($crossAssignments * 5) + ($forumActivity * 3) + ($forumMessages * 2) + ($kudosCount * 4) + ($chatMessages * 1)));

        return [
            'score' => $score,
            'cross_assignments' => $crossAssignments,
            'forum_threads' => $forumActivity,
            'forum_messages' => $forumMessages,
            'kudos' => $kudosCount,
            'chat_messages' => $chatMessages,
        ];
    }

    public function getTeamEngagement(int $teamId, ?int $days = 7): array
    {
        $wellnessService = app(WellnessMetricsService::class);
        $wellness = $wellnessService->getTeamWellness($teamId, $days);

        $teamCompletion = DB::table('activities')
            ->join('users', 'activities.created_by_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereNotJson('activities.status->value', ['archived', 'trashed'])
            ->whereBetween('activities.updated_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN JSON_EXTRACT(activities.status, \'$.value\') = \'completed\' THEN 1 ELSE 0 END) as completed
            ')
            ->first();

        $productivityRate = $teamCompletion && $teamCompletion->total > 0
            ? ($teamCompletion->completed / $teamCompletion->total) * 100
            : 0;

        $kudosCount = DB::table('kudos')
            ->join('users', 'kudos.from_user_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereBetween('kudos.created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $engagementScore = round(
            ($wellness['avg_wellness'] ?? 0) * 0.30 +
            ($productivityRate * 0.25) +
            (min(100, $kudosCount * 10) * 0.15) +
            (60 * 0.10) +
            (60 * 0.10)
        );

        return [
            'score' => $engagementScore,
            'wellness_avg' => $wellness['avg_wellness'] ?? 0,
            'productivity_rate' => round($productivityRate, 2),
            'kudos_count' => $kudosCount,
            'team_size' => $wellness['team_size'] ?? 0,
        ];
    }
}
