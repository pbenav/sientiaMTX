<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WellnessMetricsService
{
    /**
     * Map mood_label string to numeric score (1-5).
     */
    private function moodToScore(string $label): int
    {
        return match (strtolower(trim($label))) {
            'terrible', 'muy mal', 'bad', 'terrible' => 1,
            'bad', 'mal' => 2,
            'okay', 'regular', 'neutral', 'ok', 'normal' => 3,
            'good', 'bien', 'well', 'bueno' => 4,
            'great', 'muy bien', 'excellent', 'excelente', 'amazing' => 5,
            default => 3,
        };
    }

    public function getWellnessScore(?int $userId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        if ($userId === null) {
            $logs = DB::table('user_mood_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('mood_label', 'energy_level', 'created_at')
                ->orderBy('created_at')
                ->get();
        } else {
            $logs = DB::table('user_mood_logs')
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('mood_label', 'energy_level', 'created_at')
                ->orderBy('created_at')
                ->get();
        }

        if ($logs->isEmpty()) {
            return [
                'mood_index' => 0, 'stress_index' => 0,
                'energy_index' => 0, 'satisfaction_index' => 0,
                'wellness_score' => 0, 'trend' => 'stable',
            ];
        }

        $moodIndex = round($logs->avg(function ($log) {
            return $this->moodToScore($log->mood_label ?? '');
        }) * 20);

        $energyIndex = round(($logs->avg('energy_level') ?? 3) * 20);

        $stressIndex = max(0, 100 - $moodIndex);
        $satisfactionIndex = $moodIndex;

        $wellnessScore = round(($moodIndex + (100 - $stressIndex) + $energyIndex + $satisfactionIndex) / 4, 2);

        $trend = $this->calculateTrend($logs, $startDate, $days);

        return [
            'mood_index' => $moodIndex,
            'stress_index' => $stressIndex,
            'energy_index' => $energyIndex,
            'satisfaction_index' => $satisfactionIndex,
            'wellness_score' => $wellnessScore,
            'trend' => $trend,
            'period_days' => $days,
        ];
    }

    public function getBurnoutRisk(int $userId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $logs = DB::table('user_mood_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('mood_label', 'energy_level', 'notes', 'created_at')
            ->orderBy('created_at')
            ->get();

        if ($logs->isEmpty()) {
            return ['level' => 'low', 'score' => 0, 'factors' => []];
        }

        $avgMood = $logs->avg(function ($log) {
            return $this->moodToScore($log->mood_label ?? '');
        });

        $recentLogs = $logs->where('created_at', '>=', $endDate->copy()->subDays(3));
        $recentMood = $recentLogs->avg(function ($log) {
            return $this->moodToScore($log->mood_label ?? '');
        });

        $user = DB::table('users')->where('id', $userId)->first();
        $workStartHour = $user->work_start_time ?? 8;
        $workEndHour = $user->work_end_time ?? 18;

        $overtimeHours = DB::table('time_logs')
            ->where('time_logs.user_id', $userId)
            ->whereBetween('time_logs.start_at', [$startDate, $endDate])
            ->where(function ($q) use ($workStartHour, $workEndHour) {
                $q->whereRaw('HOUR(time_logs.start_at) < ? OR HOUR(time_logs.end_at) > ?', [$workStartHour, $workEndHour]);
            })
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, time_logs.start_at, time_logs.end_at)) / 60 as overtime')
            ->value('overtime') ?? 0;

        $score = 0;
        $factors = [];

        if ($recentMood < 2) {
            $score += 40;
            $factors[] = 'mood_very_low';
        } elseif ($recentMood < 3) {
            $score += 20;
            $factors[] = 'mood_low';
        }

        $avgEnergy = $logs->avg('energy_level') ?? 3;
        if ($avgEnergy < 2) {
            $score += 30;
            $factors[] = 'energy_low';
        }

        if ($overtimeHours > 5) {
            $score += 30;
            $factors[] = 'overtime_excessive';
        } elseif ($overtimeHours > 2) {
            $score += 15;
            $factors[] = 'overtime_moderate';
        }

        $level = 'low';
        if ($score >= 60) {
            $level = 'high';
        } elseif ($score >= 30) {
            $level = 'medium';
        }

        return [
            'level' => $level,
            'score' => $score,
            'factors' => $factors,
            'avg_mood' => round($avgMood * 20),
            'avg_energy' => round($avgEnergy * 20),
            'overtime_hours' => round($overtimeHours, 1),
        ];
    }

    public function getMoodHeatmap(int $userId, ?int $days = 30): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $logs = DB::table('user_mood_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('mood_label', 'energy_level', 'created_at')
            ->orderBy('created_at')
            ->get();

        $heatmap = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $dayLogs = $logs->where('created_at', '>=', $cursor->copy()->startOfDay())
                ->where('created_at', '<', $cursor->copy()->addDay());

            $moodAvg = $dayLogs->isEmpty()
                ? null
                : round($dayLogs->avg(function ($log) {
                    return $this->moodToScore($log->mood_label ?? '');
                }) * 20);

            $energyAvg = $dayLogs->isEmpty()
                ? null
                : round(($dayLogs->avg('energy_level') ?? 3) * 20);

            $heatmap[] = [
                'date' => $cursor->toDateString(),
                'mood' => $moodAvg,
                'energy' => $energyAvg,
                'stress' => $moodAvg !== null ? max(0, 100 - $moodAvg) : null,
                'satisfaction' => $moodAvg,
            ];
            $cursor->addDay();
        }

        return $heatmap;
    }

    public function getTeamMoodHeatmap(int $teamId, ?int $days = 30): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $logs = DB::table('user_mood_logs')
            ->join('team_user', 'user_mood_logs.user_id', '=', 'team_user.user_id')
            ->where('team_user.team_id', $teamId)
            ->whereBetween('user_mood_logs.created_at', [$startDate, $endDate])
            ->select('user_mood_logs.mood_label', 'user_mood_logs.energy_level', 'user_mood_logs.created_at')
            ->orderBy('user_mood_logs.created_at')
            ->get();

        $heatmap = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $dayLogs = $logs->where('created_at', '>=', $cursor->copy()->startOfDay())
                ->where('created_at', '<', $cursor->copy()->addDay());

            $moodAvg = $dayLogs->isEmpty()
                ? null
                : round($dayLogs->avg(function ($log) {
                    return WellnessMetricsService::moodToScoreStatic($log->mood_label ?? '');
                }) * 20);

            $energyAvg = $dayLogs->isEmpty()
                ? null
                : round(($dayLogs->avg('energy_level') ?? 3) * 20);

            $heatmap[] = [
                'date' => $cursor->toDateString(),
                'mood' => $moodAvg,
                'energy' => $energyAvg,
                'stress' => $moodAvg !== null ? max(0, 100 - $moodAvg) : null,
                'satisfaction' => $moodAvg,
            ];
            $cursor->addDay();
        }

        return $heatmap;
    }

    public function getTeamWellness(int $teamId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $members = DB::table('users')
            ->join('team_user', 'users.id', '=', 'team_user.user_id')
            ->where('team_user.team_id', $teamId)
            ->leftJoin('user_mood_logs', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'user_mood_logs.user_id')
                     ->whereBetween('user_mood_logs.created_at', [$startDate, $endDate]);
            })
            ->select(
                'users.id',
                'users.name',
                'user_mood_logs.mood_label',
                'user_mood_logs.energy_level'
            )
            ->groupBy('users.id', 'users.name', 'user_mood_logs.mood_label', 'user_mood_logs.energy_level')
            ->get();

        $memberScores = $members->groupBy('id')->map(function ($group) use ($teamId) {
            $member = $group->first();
            $moodScore = round($group->avg(function ($log) {
                return WellnessMetricsService::moodToScoreStatic($log->mood_label ?? '');
            }) * 20);

            $energyScore = round(($group->avg('energy_level') ?? 3) * 20);
            $stressScore = max(0, 100 - $moodScore);

            $wellnessScore = round(($moodScore + (100 - $stressScore) + $energyScore + $moodScore) / 4, 2);

            return (object) [
                'id' => $member->id,
                'name' => $member->name,
                'mood_score' => $moodScore,
                'energy_score' => $energyScore,
                'stress_score' => $stressScore,
                'satisfaction_score' => $moodScore,
                'wellness_score' => $wellnessScore,
            ];
        });

        $avgWellness = $memberScores->avg('wellness_score') ?? 0;
        $avgStress = $memberScores->avg('stress_score') ?? 0;
        $avgMood = $memberScores->avg('mood_score') ?? 0;

        $burnoutRisk = $memberScores->filter(fn($m) => $m->wellness_score < 40 && $m->stress_score > 70)->count();
        $mediumRisk = $memberScores->filter(fn($m) => $m->wellness_score < 55 && $m->stress_score > 50)->count();

        return [
            'members' => $memberScores,
            'avg_wellness' => round($avgWellness, 2),
            'avg_stress' => round($avgStress, 2),
            'avg_mood' => round($avgMood, 2),
            'team_size' => $memberScores->count(),
            'burnout_risk_count' => $burnoutRisk,
            'medium_risk_count' => $mediumRisk,
        ];
    }

    public function getWorkLifeBalance(int $userId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $user = DB::table('users')->where('id', $userId)->first();
        $workStartHour = $user->work_start_time ?? 8;
        $workEndHour = $user->work_end_time ?? 18;

        $totalHours = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, start_at, end_at)) / 60 as total')
            ->value('total') ?? 0;

        $overtimeHours = DB::table('time_logs')
            ->where('time_logs.user_id', $userId)
            ->whereBetween('time_logs.start_at', [$startDate, $endDate])
            ->where(function ($q) use ($workStartHour, $workEndHour) {
                $q->whereRaw('HOUR(time_logs.start_at) < ? OR HOUR(time_logs.end_at) > ?', [$workStartHour, $workEndHour]);
            })
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, time_logs.start_at, time_logs.end_at)) / 60 as total')
            ->value('total') ?? 0;

        $weekendHours = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->whereRaw('DAYOFWEEK(start_at) IN (1, 7)')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, start_at, end_at)) / 60 as total')
            ->value('total') ?? 0;

        $dailyGoal = 8;
        $goalCompletion = $dailyGoal > 0 ? min(100, ($totalHours / ($dailyGoal * $days)) * 100) : 0;
        $overtimePenalty = min(50, ($overtimeHours / max(1, $days)) * 10);
        $workLifeBalance = max(0, min(100, $goalCompletion - $overtimePenalty - ($weekendHours * 5)));

        return [
            'total_hours' => round($totalHours, 1),
            'overtime_hours' => round($overtimeHours, 1),
            'weekend_hours' => round($weekendHours, 1),
            'daily_goal' => $dailyGoal,
            'goal_completion' => round($goalCompletion, 1),
            'work_life_balance_index' => round($workLifeBalance, 2),
        ];
    }

    private function calculateTrend($logs, Carbon $startDate, int $days): string
    {
        if ($logs->count() < 3) {
            return 'stable';
        }

        $midpoint = $startDate->copy()->addDays($days / 2);

        $recent = $logs->where('created_at', '>=', $midpoint)->avg(function ($log) {
            return $this->moodToScore($log->mood_label ?? '');
        });

        $older = $logs->where('created_at', '<', $midpoint)->avg(function ($log) {
            return $this->moodToScore($log->mood_label ?? '');
        });

        $diff = ($recent - $older) * 20;
        $threshold = 5;

        if ($diff > $threshold) return 'improving';
        if ($diff < -$threshold) return 'declining';
        return 'stable';
    }

    /** Static version for use in static context (team wellness) */
    private static function moodToScoreStatic(string $label): int
    {
        return match (strtolower(trim($label))) {
            'terrible', 'muy mal', 'bad', 'terrible' => 1,
            'bad', 'mal' => 2,
            'okay', 'regular', 'neutral', 'ok', 'normal' => 3,
            'good', 'bien', 'well', 'bueno' => 4,
            'great', 'muy bien', 'excellent', 'excelente', 'amazing' => 5,
            default => 3,
        };
    }
}
