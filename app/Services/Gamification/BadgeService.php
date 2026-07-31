<?php

namespace App\Services\Gamification;

use App\Models\User;
use App\Models\Badge;
use App\Models\GamificationLog;
use Illuminate\Support\Facades\Log;

class BadgeService
{
    /**
     * Evalúa y otorga las medallas que le correspondan a un usuario.
     */
    public function evaluate(User $user, int $teamId = null): void
    {
        $allBadges = Badge::all();
        $userBadgeIds = $user->badges()->pluck('badges.id')->toArray();

        $enabledBadges = null;
        if ($teamId) {
            $team = \App\Models\Team::find($teamId);
            if ($team && isset($team->settings['enabled_badges'])) {
                $enabledBadges = $team->settings['enabled_badges'];
            }
        }

        foreach ($allBadges as $badge) {
            // Check if badge is enabled for this team (if the setting exists)
            if ($enabledBadges !== null && !in_array($badge->id, $enabledBadges)) {
                continue;
            }

            if (in_array($badge->id, $userBadgeIds)) {
                continue; // Ya tiene esta medalla
            }

            $meetsCriteria = $this->checkCriteria($user, $badge);

            if ($meetsCriteria) {
                $this->awardBadge($user, $badge, $teamId);
            }
        }
    }

    /**
     * Comprueba si un usuario cumple el criterio de una medalla específica.
     */
    protected function checkCriteria(User $user, Badge $badge): bool
    {
        $threshold = $badge->criteria_threshold;

        switch ($badge->criteria_type) {
            case 'login_count':
                // Asumiendo que podemos medir si ha entrado
                // Aquí simulamos que sí
                return true; 
            
            case 'streak_days':
                // Check from GamificationMetricsService
                $metricsService = app(\App\Services\Metrics\GamificationMetricsService::class);
                $data = $metricsService->getUserGamification($user->id);
                return ($data['streak'] ?? 0) >= $threshold;

            case 'completed_tasks':
                $count = \App\Models\Activity::where('created_by_id', $user->id)
                            ->whereJsonContains('status', 'completed')
                            ->count();
                return $count >= $threshold;

            case 'received_kudos':
                $count = $user->receivedKudos()->count();
                return $count >= $threshold;
            
            case 'shared_tasks':
                // Temporarily disabled due to missing activity_user table
                return false;

            case 'night_tasks':
            case 'early_tasks':
                // En un entorno real se comprobaría el horario de la actividad
                return false; 

            case 'xp_total':
                return ($user->experience_points ?? 0) >= $threshold;

            case 'resilience_points':
                return ($user->resilience_points ?? 0) >= $threshold;

            case 'user_level':
                $level = floor(($user->experience_points ?? 0) / 1000) + 1;
                return $level >= $threshold;

            case 'consistent_month':
                // Lógica de comprobación de constancia (simplificada)
                return false;

            case 'badges_count':
                return $user->badges()->count() >= $threshold;

            default:
                return false;
        }
    }

    /**
     * Otorga una medalla a un usuario.
     */
    protected function awardBadge(User $user, Badge $badge, int $teamId = null): void
    {
        $user->badges()->attach($badge->id, ['team_id' => $teamId]);

        GamificationLog::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'points' => 50, // Pequeño bono de XP por desbloquear medallas
            'type' => 'badge_unlocked',
            'source_type' => Badge::class,
            'source_id' => $badge->id,
            'description' => "¡Desbloqueaste la medalla: {$badge->name}!",
        ]);
        
        $user->increment('experience_points', 50);

        Log::info("Medalla '{$badge->name}' otorgada a {$user->name}.");
    }
}
