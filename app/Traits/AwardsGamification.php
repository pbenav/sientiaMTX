<?php

namespace App\Traits;

use App\Models\Task;
use App\Models\GamificationLog;
use Illuminate\Support\Facades\Log;

trait AwardsGamification
{
    /**
     * Award points and handle energy for gamification.
     */
    protected function awardGamificationPoints(Task $task)
    {
        // Determinamos quién debe recibir los puntos.
        // Si la tarea tiene un usuario asignado, los recibe él.
        // Si no, los recibe el usuario que la está completando (auth user).
        $user = $task->assignedUser ?? auth()->user();

        if (!$user) {
            Log::warning("No se pudo otorgar puntos de gamificación para la tarea #{$task->id}: No hay usuario asignado ni autenticado.");
            return;
        }

        $multiplier = $task->cognitive_load ?? 1;
        $xp = 10 * $multiplier;
        $resilience = 0;
        
        // --- Master Plan Race Bonus ---
        // Reward members for finishing tasks quickly relative to their peers in the same master plan.
        $raceBonusXp = 0;
        if ($task->parent_id && $task->parent && $task->parent->is_template) {
            $completedCount = $task->parent->children()
                ->where('status', 'completed')
                ->where('id', '!=', $task->id)
                ->count();
            
            if ($completedCount === 0) {
                $raceBonusXp = 15; // 1st place
            } elseif ($completedCount === 1) {
                $raceBonusXp = 10; // 2nd place
            } elseif ($completedCount === 2) {
                $raceBonusXp = 5;  // 3rd place
            }
            
            $xp += $raceBonusXp;
        }

        // --- Rediseño de Energía SientiaMTX ---
        // Antes: Drain asfixiante de $multiplier * 5.
        // Ahora: Drain suave de $multiplier * 2 y recompensa fija de +5 por "cerrar el círculo".
        // FIX: Si el usuario olvidó detener el contador, no lo castigamos con drenaje excesivo.
        // El drenaje está basado en la CARGA COGNITIVA, no en el tiempo real, lo cual ya protege 
        // contra olvidos de cronómetro. No obstante, aumentamos la recompensa por completitud 
        // si la carga es alta para balancear el esfuerzo.
        
        $energyDrain = $multiplier * 2; 
        $energyGainCompletion = 5 + ($multiplier > 3 ? 2 : 0); // Bonus energy for high load tasks
        $netEnergyChange = $energyGainCompletion - $energyDrain;

        if ($task->is_out_of_skill_tree) {
            $resilience = 20 * $multiplier; // Extra resilience scaled by load
            $xp = 5 * $multiplier; 
        }

        if ($task->is_backstage) {
            $xp += (5 * $multiplier); // Bonus for preparation tasks
        }

        // Update User global points
        $user->increment('experience_points', $xp);
        $user->increment('resilience_points', $resilience);
        
        // Skill-specific XP
        $rawSkillIds = $task->skills()->pluck('skills.id')->toArray();
        if ($task->skill_id && !in_array($task->skill_id, $rawSkillIds)) {
            $rawSkillIds[] = $task->skill_id;
        }

        $skillIds = [];
        foreach ($rawSkillIds as $sId) {
            $skill = \App\Models\Skill::find($sId);
            if ($skill && $skill->team_id === null) {
                // If common skill, check if team has a shadowed local one
                $shadowed = \App\Models\Skill::where('team_id', $task->team_id)
                    ->where('name', $skill->name)
                    ->first();
                $skillIds[] = $shadowed ? $shadowed->id : $sId;
            } else {
                $skillIds[] = $sId;
            }
        }
        $skillIds = array_unique($skillIds);

        foreach ($skillIds as $sId) {
            $userSkill = $user->skills()->where('skill_id', $sId)->first();
            if (!$userSkill) {
                $user->skills()->attach($sId, ['total_xp' => $xp, 'level' => 1]);
            } else {
                $newTotalXp = $userSkill->pivot->total_xp + $xp;
                
                // Simple leveling logic: Level 1: 0, Level 2: 50, Level 3: 150, Level 4: 350, Level 5: 750
                $newLevel = $userSkill->pivot->level;
                $levelThresholds = [1 => 0, 2 => 30, 3 => 100, 4 => 300, 5 => 1000];
                foreach ($levelThresholds as $level => $threshold) {
                    if ($newTotalXp >= $threshold) {
                        $newLevel = $level;
                    }
                }
                
                $user->skills()->updateExistingPivot($sId, [
                    'total_xp' => $newTotalXp,
                    'level' => $newLevel
                ]);
            }
        }

        // La energía ahora fluye dinámicamente. Nunca menos de 0, máximo 100.
        $newEnergy = min(100, max(0, ($user->energy_level ?? 100) + $netEnergyChange));
        $user->update(['energy_level' => $newEnergy]);

        // Log the achievement
        $description = __('gamification.completed_description', ['title' => $task->title]);
        
        if ($resilience > 0) {
            $description .= " (" . __('gamification.resilience_challenge') . ")";
        }
        
        if ($raceBonusXp > 0) {
            $place = $raceBonusXp === 15 ? __('gamification.first_place') : 
                    ($raceBonusXp === 10 ? __('gamification.second_place') : __('gamification.third_place'));
                    
            $description .= " " . __('gamification.race_bonus_description', ['points' => $raceBonusXp, 'place' => $place]);
        }

        GamificationLog::create([
            'user_id' => $user->id,
            'team_id' => $task->team_id,
            'points' => $xp + $resilience,
            'type' => $resilience > 0 ? 'resilience' : 'task',
            'source_type' => 'App\Models\Task',
            'source_id' => $task->id,
            'description' => $description,
        ]);

        Log::info("Gamificación: Otorgados {$xp} XP y {$resilience} Resilience a {$user->name} por tarea #{$task->id}.");
    }

    /**
     * Award points for reporting a service status (verified first alert).
     */
    protected function awardServiceReportingPoints($user, $teamId, $description)
    {
        if (!$user) return;

        $xp = 20; // Proactive reporting is highly valued
        $energyGain = 5; // A small boost for being alert

        $user->increment('experience_points', $xp);
        $newEnergy = min(100, max(0, ($user->energy_level ?? 100) + $energyGain));
        $user->update(['energy_level' => $newEnergy]);

        GamificationLog::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'points' => $xp,
            'type' => 'proactivity',
            'source_type' => 'App\Models\Service',
            'source_id' => null,
            'description' => $description,
        ]);

        Log::info("Gamificación: Otorgados {$xp} XP a {$user->name} por reporte de servicio verificado.");
    }
}
