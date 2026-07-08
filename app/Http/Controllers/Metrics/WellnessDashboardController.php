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

        $memberWellness = [];
        if ($team) {
            $members = \DB::table('users')->where('favorite_team_id', $team->id)->get();
            $members->each(function ($member) use (&$memberWellness, $wellness) {
                $score = $wellness->getWellnessScore($member->id, 7);
                $memberWellness[] = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'score' => $score['wellness_score'],
                    'level' => $this->scoreLevel($score['wellness_score']),
                ];
            });
        }

        $moodHeatmap = $wellness->getMoodHeatmap($user->id, 30);

        $burnoutRisks = [];
        if ($team) {
            $members = \DB::table('users')->where('favorite_team_id', $team->id)->get();
            $members->each(function ($member) use (&$burnoutRisks, $wellness) {
                $risk = $wellness->getBurnoutRisk($member->id, 30);
                if ($risk['level'] === 'high') {
                    $burnoutRisks[] = [
                        'id' => $member->id,
                        'name' => $member->name,
                        'score' => $risk['score'],
                        'level' => $risk['level'],
                        'factors' => $risk['factors'],
                    ];
                }
            });
        }

        $workLifeBalance = $wellness->getWorkLifeBalance($user->id, 7);

        $recommendations = [];
        if ($teamWellness && $teamWellness['avg_wellness'] < 60) {
            $recommendations[] = 'El bienestar del equipo está por debajo del nivel óptimo. Considera revisar la carga de trabajo.';
        }
        if (!empty($burnoutRisks)) {
            $recommendations[] = count($burnoutRisks) . ' miembro(s) con riesgo de burnout detectado(s). Programa sesiones de seguimiento.';
        }
        if ($workLifeBalance['work_life_balance_index'] < 60) {
            $recommendations[] = 'El balance trabajo-vida personal necesita atención. Revisa las horas extra.';
        }
        if (empty($recommendations)) {
            $recommendations[] = 'El bienestar del equipo se mantiene en niveles saludables. Continúa fomentando hábitos positivos.';
        }

        return view('metrics.wellness.dashboard', compact(
            'user', 'team', 'teamWellness', 'memberWellness', 'moodHeatmap',
            'burnoutRisks', 'workLifeBalance', 'recommendations', 'days'
        ));
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
