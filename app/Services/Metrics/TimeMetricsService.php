<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimeMetricsService
{
    /** Duration in minutes between start_at and end_at */
    private const DURATION_EXPR = 'TIMESTAMPDIFF(MINUTE, start_at, end_at)';

    public function getTimeOverview(int $userId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $dailyGoal = 8;

        $totalMinutes = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->selectRaw('SUM(' . self::DURATION_EXPR . ') as total')
            ->value('total') ?? 0;

        $totalHours = round($totalMinutes / 60, 1);
        $goalHours = $dailyGoal * $days;
        $goalCompletion = $goalHours > 0 ? min(100, round(($totalHours / $goalHours) * 100, 1)) : 0;

        $user = DB::table('users')->where('id', $userId)->first();
        $workStartHour = $user->work_start_time ?? 8;
        $workEndHour = $user->work_end_time ?? 18;

        $overtimeMinutes = DB::table('time_logs')
            ->where('time_logs.user_id', $userId)
            ->whereBetween('time_logs.start_at', [$startDate, $endDate])
            ->where(function ($q) use ($workStartHour, $workEndHour) {
                $q->whereRaw('HOUR(time_logs.start_at) < ? OR HOUR(time_logs.end_at) > ?', [$workStartHour, $workEndHour]);
            })
            ->selectRaw('SUM(' . self::DURATION_EXPR . ') as total')
            ->value('total') ?? 0;

        $weekendMinutes = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->whereRaw('DAYOFWEEK(start_at) IN (1, 7)')
            ->selectRaw('SUM(' . self::DURATION_EXPR . ') as total')
            ->value('total') ?? 0;

        $daysOnGoal = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->selectRaw('DATE(start_at) as day, SUM(' . self::DURATION_EXPR . ') / 60 as hours')
            ->groupBy('day')
            ->havingRaw('hours >= ?', [$dailyGoal])
            ->count();

        $goalRate = $days > 0 ? round(($daysOnGoal / $days) * 100, 1) : 0;

        return [
            'total_hours' => $totalHours,
            'daily_goal' => $dailyGoal,
            'goal_completion' => $goalCompletion,
            'goal_rate' => $goalRate,
            'overtime_hours' => round($overtimeMinutes / 60, 1),
            'weekend_hours' => round($weekendMinutes / 60, 1),
            'days_on_goal' => $daysOnGoal,
            'total_days' => $days,
        ];
    }

    public function getDailyHours(int $userId, ?int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);
        $dailyGoal = 8;

        $rows = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->selectRaw('DATE(start_at) as day, SUM(' . self::DURATION_EXPR . ') / 60 as hours')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $result = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $dayStr = $cursor->toDateString();
            $row = $rows->firstWhere('day', $dayStr);
            $result[] = [
                'date' => $dayStr,
                'hours' => $row ? round((float) $row->hours, 1) : 0,
                'goal' => $dailyGoal,
            ];
            $cursor->addDay();
        }

        return $result;
    }

    public function getTimeDistributionByType(int $userId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $total = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, Carbon::now()])
            ->selectRaw('SUM(' . self::DURATION_EXPR . ') as total')
            ->value('total') ?? 1;

        $rows = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, Carbon::now()])
            ->selectRaw('type, SUM(' . self::DURATION_EXPR . ') as minutes')
            ->groupBy('type')
            ->orderByDesc('minutes')
            ->get();

        return $rows->map(function ($row) use ($total) {
            return [
                'type' => $row->type,
                'minutes' => (int) $row->minutes,
                'hours' => round($row->minutes / 60, 1),
                'percentage' => round(($row->minutes / $total) * 100, 1),
            ];
        })->toArray();
    }

    public function getFragmentation(int $userId, ?int $days = 7): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $dailySegments = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, Carbon::now()])
            ->selectRaw('DATE(start_at) as day, COUNT(*) as segment_count')
            ->groupBy('day')
            ->get();

        $avgSegments = $dailySegments->avg('segment_count') ?? 0;

        $longSessions = DB::table('time_logs')
            ->where('user_id', $userId)
            ->whereBetween('start_at', [$startDate, Carbon::now()])
            ->whereRaw(self::DURATION_EXPR . ' > 120')
            ->count();

        return [
            'avg_daily_segments' => round($avgSegments, 1),
            'on_hold_time_hours' => 0,
            'total_sessions' => DB::table('time_logs')
                ->where('user_id', $userId)
                ->whereBetween('start_at', [$startDate, Carbon::now()])
                ->count(),
            'long_sessions' => $longSessions,
        ];
    }

    public function getOvertimeTrend(int $userId, ?int $weeks = 4): array
    {
        $result = [];
        $endDate = Carbon::now();

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = $endDate->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

            $user = DB::table('users')->where('id', $userId)->first();
            $workStartHour = $user->work_start_time ?? 8;
            $workEndHour = $user->work_end_time ?? 18;

            $overtime = DB::table('time_logs')
                ->where('time_logs.user_id', $userId)
                ->whereBetween('time_logs.start_at', [$weekStart, $weekEnd])
                ->where(function ($q) use ($workStartHour, $workEndHour) {
                    $q->whereRaw('HOUR(time_logs.start_at) < ? OR HOUR(time_logs.end_at) > ?', [$workStartHour, $workEndHour]);
                })
                ->selectRaw('SUM(' . self::DURATION_EXPR . ') / 60 as overtime')
                ->value('overtime') ?? 0;

            $result[] = [
                'week' => $weekStart->toDateString(),
                'label' => $weekStart->format('d/M'),
                'overtime_hours' => round($overtime, 1),
            ];
        }

        return $result;
    }
}
