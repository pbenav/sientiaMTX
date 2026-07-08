<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationMetricsService
{
    public function getUserGamification(int $userId): array
    {
        $totalPoints = DB::table('gamification_logs')
            ->where('user_id', $userId)
            ->where('type', 'points')
            ->selectRaw('SUM(points) as total')
            ->value('total') ?? 0;

        $badges = DB::table('gamification_logs')
            ->where('user_id', $userId)
            ->where('type', 'badge')
            ->selectRaw('COUNT(DISTINCT source_type) as count, GROUP_CONCAT(DISTINCT source_type) as badge_list')
            ->first();

        $streaks = DB::table('gamification_logs')
            ->where('user_id', $userId)
            ->where('type', 'streak')
            ->orderByDesc('created_at')
            ->first();

        $kudosReceived = DB::table('kudos')
            ->where('to_user_id', $userId)
            ->count();

        $kudosSent = DB::table('kudos')
            ->where('from_user_id', $userId)
            ->count();

        $level = $this->calculateLevel((int) $totalPoints);

        return [
            'total_points' => (int) $totalPoints,
            'level' => $level,
            'next_level_points' => $this->getNextLevelPoints($level),
            'badges_count' => $badges->count ?? 0,
            'badges' => $badges->badge_list ? json_decode($badges->badge_list, true) : [],
            'active_streaks' => $streaks ? 1 : 0,
            'kudos_received' => $kudosReceived,
            'kudos_sent' => $kudosSent,
            'kudos_ratio' => $kudosReceived > 0 ? round($kudosSent / $kudosReceived, 2) : $kudosSent,
        ];
    }

    public function getLeaderboard(int $teamId, ?string $period = 'weekly'): array
    {
        $now = Carbon::now();
        $startDate = match ($period) {
            'weekly' => $now->copy()->startOfWeek(),
            'monthly' => $now->copy()->startOfMonth(),
            default => $now->copy()->subMonths(3),
        };

        $points = DB::table('gamification_logs')
            ->where('type', 'points')
            ->whereBetween('created_at', [$startDate, $now])
            ->selectRaw('user_id, SUM(points) as total_points')
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit(50)
            ->get();

        $leaderboard = $points->map(function ($entry, $index) use ($startDate) {
            $user = DB::table('users')
                ->where('id', $entry->user_id)
                ->select('id', 'name', 'email', 'profile_photo_path as avatar')
                ->first();

            if (!$user) return null;

            $badges = DB::table('gamification_logs')
                ->where('user_id', $entry->user_id)
                ->where('type', 'badge')
                ->whereBetween('created_at', [$startDate, $now])
                ->selectRaw('COUNT(DISTINCT source_type) as count')
                ->value('count') ?? 0;

            return [
                'position' => $index + 1,
                'user_id' => $entry->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'points' => (int) $entry->total_points,
                'badges' => (int) $badges,
                'level' => $this->calculateLevel((int) $entry->total_points),
            ];
        })->filter()->values()->toArray();

        return $leaderboard;
    }

    public function getBadgeDistribution(int $teamId, ?int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('gamification_logs')
            ->join('users', 'gamification_logs.user_id', '=', 'users.id')
            ->where('gamification_logs.type', 'badge')
            ->whereBetween('gamification_logs.created_at', [$startDate, Carbon::now()])
            ->where('users.favorite_team_id', $teamId)
            ->selectRaw('gamification_logs.source_type, COUNT(*) as count')
            ->groupBy('source_type')
            ->orderByDesc('count')
            ->limit(15)
            ->get();

        return $rows->toArray();
    }

    public function getStreakLeaderboard(int $teamId, ?int $limit = 10): array
    {
        return DB::table('gamification_logs')
            ->join('users', 'gamification_logs.user_id', '=', 'users.id')
            ->where('gamification_logs.type', 'streak')
            ->where('users.favorite_team_id', $teamId)
            ->selectRaw('users.id, users.name, COUNT(*) as max_streak')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('max_streak')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getKudosDistribution(int $teamId, ?int $days = 30): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $sent = DB::table('kudos')
            ->join('users as sender', 'kudos.from_user_id', '=', 'sender.id')
            ->where('sender.favorite_team_id', $teamId)
            ->whereBetween('kudos.created_at', [$startDate, Carbon::now()])
            ->selectRaw('sender.name, COUNT(*) as count')
            ->groupBy('sender.id', 'sender.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['type' => 'sent', 'name' => $r->name, 'count' => (int) $r->count])
            ->toArray();

        $received = DB::table('kudos')
            ->join('users as receiver', 'kudos.to_user_id', '=', 'receiver.id')
            ->where('receiver.favorite_team_id', $teamId)
            ->whereBetween('kudos.created_at', [$startDate, Carbon::now()])
            ->selectRaw('receiver.name, COUNT(*) as count')
            ->groupBy('receiver.id', 'receiver.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['type' => 'received', 'name' => $r->name, 'count' => (int) $r->count])
            ->toArray();

        return array_merge($sent, $received);
    }

    public function getEngagementScore(int $userId, ?int $days = 7): array
    {
        $totalPoints = DB::table('gamification_logs')
            ->where('user_id', $userId)
            ->where('type', 'points')
            ->whereBetween('created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->selectRaw('SUM(points) as total')
            ->value('total') ?? 0;

        $kudosReceived = DB::table('kudos')
            ->where('to_user_id', $userId)
            ->whereBetween('created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $activitiesCompleted = DB::table('activities')
            ->where('created_by_id', $userId)
            ->whereJsonContains('status->value', 'completed')
            ->whereBetween('updated_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $wellnessService = app(WellnessMetricsService::class);
        $wellness = $wellnessService->getWellnessScore($userId, $days);

        $pointsNorm = min(100, $totalPoints / 10);
        $kudosNorm = min(100, $kudosReceived * 10);
        $completionsNorm = min(100, $activitiesCompleted * 5);
        $wellnessNorm = $wellness['wellness_score'] ?? 0;

        $engagementScore = round(
            ($pointsNorm * 0.25) + ($kudosNorm * 0.20) + ($completionsNorm * 0.20) +
            ($wellnessNorm * 0.15) + (60 * 0.10) + (60 * 0.10),
            2
        );

        return [
            'score' => $engagementScore,
            'points_norm' => round($pointsNorm, 2),
            'kudos_norm' => round($kudosNorm, 2),
            'completions_norm' => round($completionsNorm, 2),
            'wellness_norm' => round($wellnessNorm, 2),
            'total_points' => (int) $totalPoints,
            'kudos_received' => $kudosReceived,
            'activities_completed' => $activitiesCompleted,
        ];
    }

    public function getRecentAchievements(int $userId, ?int $limit = 10): array
    {
        return DB::table('gamification_logs')
            ->where('user_id', $userId)
            ->whereIn('type', ['badge', 'points', 'streak', 'kudos'])
            ->select('type', 'source_type', 'points', 'message', 'created_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getTeamEngagement(int $teamId, ?int $days = 7): array
    {
        $kudosCount = DB::table('kudos')
            ->join('users', 'kudos.from_user_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereBetween('kudos.created_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $activitiesCompleted = DB::table('activities')
            ->join('users', 'activities.created_by_id', '=', 'users.id')
            ->where('users.favorite_team_id', $teamId)
            ->whereJsonContains('activities.status->value', 'completed')
            ->whereBetween('activities.updated_at', [Carbon::now()->copy()->subDays($days), Carbon::now()])
            ->count();

        $engagementScore = round(
            (min(100, $kudosCount * 10) * 0.50) +
            (min(100, $activitiesCompleted * 5) * 0.50)
        );

        return [
            'score' => $engagementScore,
            'kudos_count' => $kudosCount,
            'activities_completed' => $activitiesCompleted,
            'team_size' => DB::table('users')->where('favorite_team_id', $teamId)->count(),
        ];
    }

    private function calculateLevel(int $points): int
    {
        if ($points >= 5000) return 50;
        if ($points >= 3000) return 40;
        if ($points >= 2000) return 30;
        if ($points >= 1000) return 20;
        if ($points >= 500) return 15;
        if ($points >= 200) return 10;
        if ($points >= 100) return 7;
        if ($points >= 50) return 5;
        if ($points >= 20) return 3;
        return 1;
    }

    private function getNextLevelPoints(int $currentLevel): int
    {
        $levels = [1 => 20, 3 => 50, 5 => 100, 7 => 200, 10 => 500, 15 => 1000, 20 => 2000, 30 => 3000, 40 => 5000];
        return $levels[$currentLevel] ?? ($currentLevel * 200);
    }
}
