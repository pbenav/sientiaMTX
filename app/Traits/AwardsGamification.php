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
        $energyDrain = $multiplier * 5; // Drain energy based on cognitive load

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

        // Energy can't go below 0
        $newEnergy = max(0, ($user->energy_level ?? 100) - $energyDrain);
        $user->update(['energy_level' => $newEnergy]);

        // Log the achievement
        GamificationLog::create([
            'user_id' => $user->id,
            'team_id' => $task->team_id,
            'points' => $xp + $resilience,
            'type' => $resilience > 0 ? 'resilience' : 'task',
            'source_type' => 'App\Models\Task',
            'source_id' => $task->id,
            'description' => "Completada: " . $task->title . ($resilience > 0 ? " (Reto de Resiliencia)" : ""),
        ]);

        Log::info("Gamificación: Otorgados {$xp} XP y {$resilience} Resilience a {$user->name} por tarea #{$task->id}.");
    }
}
