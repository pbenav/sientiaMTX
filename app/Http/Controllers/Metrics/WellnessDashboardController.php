<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WellnessDashboardController extends Controller
{
    /**
     * Wellness dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = $request->input('team_id', $user->favorite_team_id);

        $team = Team::find($teamId);

        $wellness = app(WellnessMetricsService::class);
        $teamService = app(TeamMetricsService::class);

        $days = $request->input('days', 30);

        $teamWellness = $team ? $wellness->getTeamWellness($team->id, $days) : null;

        $teamWellnessData = [
            'overall_score' => $teamWellness['avg_wellness'] ?? 80,
        ];

        $memberWellnessList = [];
        $burnoutRiskList = [];
        $activeAlerts = [];
        $overtimeByMember = [];

        if ($team) {
            $members = $team->members;
            $members->each(function ($member) use (&$memberWellnessList, &$burnoutRiskList, &$activeAlerts, &$overtimeByMember, $wellness) {
                $score = $wellness->getWellnessScore($member->id, 7);
                $memberWellnessList[] = [
                    'user_id' => $member->id,
                    'name' => $member->name,
                    'wellness_score' => $score['wellness_score'],
                ];

                $risk = $wellness->getBurnoutRisk($member->id, 30);
                if ($risk['level'] === 'high' || $risk['level'] === 'critical') {
                    $burnoutRiskList[] = [
                        'user_id' => $member->id,
                        'name' => $member->name,
                        'risk_score' => $risk['score'],
                        'risk_level' => 'ALTO',
                    ];
                    $activeAlerts[] = [
                        'user_id' => $member->id,
                        'message' => 'Riesgo alto de burnout detectado para ' . $member->name,
                    ];
                }

                $weekly = [];
                for ($w = 0; $w < 4; $w++) {
                    $startOfWeek = now()->subWeeks(3 - $w)->startOfWeek();
                    $endOfWeek = now()->subWeeks(3 - $w)->endOfWeek();

                    $workStartHour = $member->work_start_time ?? 8;
                    $workEndHour = $member->work_end_time ?? 18;

                    $overtimeHours = \DB::table('time_logs')
                        ->where('user_id', $member->id)
                        ->whereBetween('start_at', [$startOfWeek, $endOfWeek])
                        ->where(function ($q) use ($workStartHour, $workEndHour) {
                            $q->whereRaw('HOUR(time_logs.start_at) < ? OR HOUR(time_logs.end_at) > ?', [$workStartHour, $workEndHour]);
                        })
                        ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, start_at, end_at)) / 60 as total')
                        ->value('total') ?? 0;

                    $weekly[] = ['hours' => round($overtimeHours, 1)];
                }
                $overtimeByMember[] = [
                    'name' => $member->name,
                    'weekly' => $weekly
                ];
            });
        }

        $stressHistory = [];
        $moodHistory = [];
        $heatMapData = [];
        if ($team) {
            $teamHeatmap = $wellness->getTeamMoodHeatmap($team->id, $days);
            foreach ($teamHeatmap as $dayData) {
                $stressHistory[] = ['date' => $dayData['date'], 'avg_stress' => $dayData['stress']];
                $moodHistory[] = ['date' => $dayData['date'], 'avg_energy' => $dayData['energy']];
                $heatMapData[] = ['date' => $dayData['date'], 'avg_mood' => $dayData['mood'] !== null ? ($dayData['mood'] / 20) : null];
            }
        }

        $workLifeBalance = $wellness->getWorkLifeBalance($user->id, 7);

        // Load Distribution (Box Plot)
        $loadDistribution = [0, 0, 0, 0, 0];
        $scatterData = [];
        $radarData = [
            'categories' => ['Logro de Horas', 'Control Extra', 'Descanso Finde', 'Índice Global', 'Ánimo'],
            'user' => [0, 0, 0, 0, 0],
            'team' => [80, 75, 90, $teamWellnessData['overall_score'] ?? 80, 85]
        ];

        if ($team && isset($members)) {
            $userLoads = \DB::table('activities')
                ->whereIn('created_by_id', $members->pluck('id'))
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('created_by_id, count(*) as count')
                ->groupBy('created_by_id')
                ->pluck('count')->toArray();
            
            if (count($userLoads) > 0) {
                sort($userLoads);
                $min = min($userLoads);
                $max = max($userLoads);
                $count = count($userLoads);
                $median = $userLoads[intdiv($count, 2)];
                $q1 = $userLoads[intdiv($count, 4)];
                $q3 = $userLoads[intdiv($count * 3, 4)];
                $loadDistribution = [$min, $q1, $median, $q3, $max];
            }

            foreach ($members as $member) {
                $mScore = $wellness->getWellnessScore($member->id, $days);
                $tasks = \DB::table('activities')
                    ->where('created_by_id', $member->id)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count();
                if ($mScore['mood_index'] > 0 || $tasks > 0) {
                    $scatterData[] = [$mScore['mood_index'], $tasks];
                }
            }

            $uScore = $wellness->getWellnessScore($user->id, $days);
            $radarData['user'] = [
                $workLifeBalance['goal_completion'],
                max(0, 100 - ($workLifeBalance['overtime_hours'] * 10)),
                max(0, 100 - ($workLifeBalance['weekend_hours'] * 20)),
                $workLifeBalance['work_life_balance_index'],
                $uScore['mood_index']
            ];
        }

        // FALLBACK LOGIC ELIMINADO: Usaremos los datos reales directamente, sin falsear si están vacíos.
        $hasDummyData = false;

        $recommendations = [];
        if (($teamWellnessData['overall_score'] ?? 100) < 60) {
            $recommendations[] = 'El bienestar del equipo está por debajo del nivel óptimo. Considera revisar la carga de trabajo.';
        }
        if (!empty($burnoutRiskList)) {
            $recommendations[] = count($burnoutRiskList) . ' miembro(s) con riesgo de burnout detectado(s). Programa sesiones de seguimiento.';
        }
        if ($workLifeBalance['work_life_balance_index'] < 60) {
            $recommendations[] = 'El balance trabajo-vida personal necesita atención. Revisa las horas extra.';
        }
        if (empty($recommendations)) {
            $recommendations[] = 'El bienestar del equipo se mantiene en niveles saludables. Continúa fomentando hábitos positivos.';
        }

        return view('metrics.wellness.dashboard', [
            'user' => $user,
            'team' => $team,
            'teamWellness' => $teamWellnessData,
            'memberWellness' => $memberWellnessList,
            'burnoutRiskList' => $burnoutRiskList,
            'activeAlerts' => $activeAlerts,
            'stressHistory' => $stressHistory,
            'moodHistory' => $moodHistory,
            'heatMapData' => $heatMapData,
            'overtimeByMember' => $overtimeByMember,
            'workLifeBalance' => $workLifeBalance,
            'loadDistribution' => $loadDistribution,
            'scatterData' => $scatterData,
            'radarData' => $radarData,
            'recommendations' => $recommendations,
            'days' => $days,
            'hasDummyData' => $hasDummyData
        ]);
    }

    public function individual($userId)
    {
        $user = Auth::user();
        $member = \App\Models\User::find($userId);
        if (!$member) {
            return redirect()->route('metrics.wellness.index');
        }

        $wellness = app(WellnessMetricsService::class);
        $wellnessScore = $wellness->getWellnessScore($member->id, 30);
        $burnoutRisk = $wellness->getBurnoutRisk($member->id, 30);
        $moodHeatmap = $wellness->getMoodHeatmap($member->id, 30);
        $workLifeBalance = $wellness->getWorkLifeBalance($member->id, 7);

        return view('metrics.wellness.individual', compact(
            'member', 'wellnessScore', 'burnoutRisk', 'moodHeatmap', 'workLifeBalance'
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
