<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\TimeMetricsService;
use App\Services\Metrics\GamificationMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalDashboardController extends Controller
{
    /**
     * Daily personal dashboard.
     */
    public function daily()
    {
        $user = Auth::user();
        $wellness = app(WellnessMetricsService::class);
        $productivity = app(ProductivityMetricsService::class);
        $time = app(TimeMetricsService::class);
        $gamification = app(GamificationMetricsService::class);

        $today = now();
        $wellnessScore = $wellness->getWellnessScore($user->id, 7);
        $productivityScore = $productivity->getProductivityScore($user->id, 7);
        $timeOverview = $time->getTimeOverview($user->id, 7);
        $dailyHours = $time->getDailyHours($user->id, 7);
        $fragmentation = $time->getFragmentation($user->id, 7);
        $blocked = $productivity->getBlockedActivities($user->id, 7);
        $gamificationData = $gamification->getUserGamification($user->id);
        $engagement = $gamification->getEngagementScore($user->id, 7);
        $streak = $gamificationData['streak'] ?? 0;
        $points = $gamificationData['points'] ?? 0;

        $todayActivities = \DB::table('activities')
            ->where('created_by_id', $user->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $urgentImportant = \DB::table('activities')
            ->where('created_by_id', $user->id)
            ->where(function ($q) {
                $q->whereJsonContains('status', 'in_progress')
                  ->orWhereJsonContains('status', 'pending');
            })
            ->where('priority', 'high')
            ->count();

        $importantNotUrgent = \DB::table('activities')
            ->where('created_by_id', $user->id)
            ->where(function ($q) {
                $q->whereJsonContains('status', 'in_progress')
                  ->orWhereJsonContains('status', 'pending');
            })
            ->where('priority', 'medium')
            ->count();

        $urgentNotImportant = \DB::table('activities')
            ->where('created_by_id', $user->id)
            ->where(function ($q) {
                $q->whereJsonContains('status', 'in_progress')
                  ->orWhereJsonContains('status', 'pending');
            })
            ->where('priority', 'low')
            ->count();

        $latestMood = \DB::table('user_mood_logs')
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->first();

        $nextAppointment = \DB::table('appointments')
            ->where('user_id', $user->id)
            ->where('appointment_date', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date')
            ->first();

        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Buenos días' : ($hour < 18 ? 'Buenas tardes' : 'Buenas noches');

        $motivationalQuotes = [
            ['text' => 'El éxito es la suma de pequeños esfuerzos repetidos día tras día.', 'author' => 'Robert Collier'],
            ['text' => 'No cuentes los días, haz que los días cuenten.', 'author' => 'Muhammad Ali'],
            ['text' => 'La disciplina es el puente entre metas y logros.', 'author' => 'Jim Rohn'],
            ['text' => 'Cada día es una nueva oportunidad para cambiar tu vida.', 'author' => 'Anónimo'],
            ['text' => 'Lo que hoy parece imposible, mañana será tu rutina.', 'author' => 'Anónimo'],
            ['text' => 'La productividad nunca es un accidente. Es siempre el resultado de un esfuerzo por conocer la misión, el trabajo dedicado y la excelencia.', 'author' => 'Paul J. Meyer'],
            ['text' => 'El único modo de hacer un gran trabajo es amar lo que haces.', 'author' => 'Steve Jobs'],
        ];
        $motivationalQuote = $motivationalQuotes[now()->day % count($motivationalQuotes)];

        // Build daily scores for sparklines (last 7 days)
        $productivityData = [
            'daily_scores' => [],
        ];
        $wellnessData = [
            'weekly_completed' => [],
            'weekly_new' => [],
        ];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->copy()->subDays($i);
            $prodScore = \DB::table('activities')
                ->where('created_by_id', $user->id)
                ->whereDate('created_at', $date)
                ->count();
            $productivityData['daily_scores'][] = $prodScore > 0 ? min(100, $prodScore * 10) : 0;

            $wellnessData['weekly_completed'][] = \DB::table('activities')
                ->where('created_by_id', $user->id)
                ->whereDate('updated_at', $date)
                ->whereJsonContains('status', 'completed')
                ->count();
            $wellnessData['weekly_new'][] = \DB::table('activities')
                ->where('created_by_id', $user->id)
                ->whereDate('created_at', $date)
                ->count();
        }

        return view('metrics.personal.daily', compact(
            'user', 'today', 'wellnessScore', 'productivityScore',
            'timeOverview', 'dailyHours', 'fragmentation', 'blocked',
            'points', 'streak', 'engagement',
            'todayActivities', 'urgentImportant', 'importantNotUrgent',
            'urgentNotImportant', 'latestMood', 'nextAppointment', 'greeting',
            'productivityData', 'wellnessData'
        ));
    }

    /**
     * Weekly personal dashboard.
     */
    public function weekly()
    {
        $user = Auth::user();
        $wellness = app(WellnessMetricsService::class);
        $productivity = app(ProductivityMetricsService::class);
        $time = app(TimeMetricsService::class);
        $gamification = app(GamificationMetricsService::class);

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $productivityScore = $productivity->getProductivityScore($user->id, 7);
        $completionRate = $productivity->getCompletionRate($user->id, 7);
        $onTimeRate = $productivity->getOnTimeDelivery($user->id, 7);
        $wellnessScore = $wellness->getWellnessScore($user->id, 7);
        $timeOverview = $time->getTimeOverview($user->id, 7);
        $fragmentation = $time->getFragmentation($user->id, 7);
        $burnoutRisk = $wellness->getBurnoutRisk($user->id, 30);
        $gamificationData = $gamification->getUserGamification($user->id);
        $streak = $gamificationData['streak'] ?? 0;
        $points = $gamificationData['points'] ?? 0;

        $dailyBreakdown = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $completed = \DB::table('activities')
                ->where('created_by_id', $user->id)
                ->whereDate('updated_at', $date)
                ->whereJsonContains('status', 'completed')
                ->count();

            $loggedHours = \DB::table('time_logs')
                ->where('user_id', $user->id)
                ->whereDate('start_at', $date)
                ->get()
                ->map(function ($log) {
                    $start = \Carbon\Carbon::parse($log->start_at);
                    $end = \Carbon\Carbon::parse($log->end_at);
                    return $start->diffInHours($end) + ($start->diffInMinutes($end) % 60) / 60;
                })->sum();

            $dailyBreakdown[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'completed' => $completed,
                'hours' => round($loggedHours, 1),
            ];
        }

        $sprintGoals = [
            ['title' => 'Completar 80% de actividades', 'target' => 80, 'current' => round($completionRate * 100, 1)],
            ['title' => 'Mantener racha activa', 'target' => 7, 'current' => min(7, $streak)],
            ['title' => 'Horas dentro de meta', 'target' => $user->time_goal_hours ?? 40, 'current' => round($timeOverview['total_hours'], 1)],
        ];

        $insights = [];
        if ($productivityScore['score'] > 80) {
            $insights[] = '¡Excelente productividad esta semana! Mantienes un ritmo de trabajo óptimo.';
        }
        if ($burnoutRisk['score'] > 50) {
            $insights[] = 'Tu riesgo de burnout está elevado. Considera tomar descansos más frecuentes.';
        }
        if ($timeOverview['goal_completion'] < 60) {
            $insights[] = 'Tus horas logradas están por debajo de tu meta. Revisa tu gestión del tiempo.';
        }
        if ($fragmentation['score'] > 60) {
            $insights[] = 'Tu día está muy fragmentado. Intenta bloques de trabajo más largos sin interrupciones.';
        }
        if (empty($insights)) {
            $insights[] = 'Tu rendimiento semanal es equilibrado. Sigue así.';
        }

        return view('metrics.personal.weekly', compact(
            'user', 'weekStart', 'weekEnd', 'productivityScore', 'wellnessScore',
            'timeOverview', 'completionRate', 'onTimeRate', 'streak',
            'points', 'burnoutRisk', 'fragmentation',
            'dailyBreakdown', 'sprintGoals', 'insights'
        ));
    }
}
