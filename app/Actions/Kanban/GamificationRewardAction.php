<?php

namespace App\Actions\Kanban;

use App\Models\Activity;
use App\Models\GamificationLog;
use Illuminate\Support\Facades\Log;

class GamificationRewardAction
{
    /**
     * Awards gamification points for a completed task.
     * Extracted from AwardsGamification trait to be reusable.
     */
    public function execute(Activity $task): void
    {
        $user = $task->assignedUser ?? ($task->assignedTo->first() ?? auth()->user());

        if (!$user) {
            Log::warning("No se pudo otorgar puntos de gamificación para la tarea #{$task->id}: No hay usuario asignado ni autenticado.");
            return;
        }

        $multiplier = $task->cognitive_load ?? 1;
        $xp = 10 * $multiplier;
        $resilience = 0;

        // Master Plan Race Bonus
        if ($task->parent_id && $task->parent && $task->parent->is_template) {
            $childrenRelation = method_exists($task->parent, 'children')
                ? $task->parent->children()
                : $task->parent->instances();
            $completedCount = $childrenRelation
                ->where(function ($q) {
                    $q->where('status', 'completed')
                      ->orWhere('status', 'LIKE', '%"value":"completed"%')
                      ->orWhere('status', 'LIKE', '%"value":"done"%')
                      ->orWhere('status', 'LIKE', '%"value":"approved"%')
                      ->orWhere('status', 'LIKE', '%"value":"finished"%');
                })
                ->where('id', '!=', $task->id)
                ->count();

            if ($completedCount === 0) {
                $resilience = 15;
            } elseif ($completedCount === 1) {
                $resilience = 10;
            } elseif ($completedCount === 2) {
                $resilience = 5;
            }
        }

        // Overdue bonus
        if ($task->due_date && $task->due_date->isPast() && !$task->isCompleted()) {
            $xp += 5;
        }

        $user->increment('xp', $xp);
        $user->increment('resilience', $resilience);

        GamificationLog::create([
            'user_id' => $user->id,
            'activity_id' => $task->id,
            'xp_awarded' => $xp,
            'resilience_awarded' => $resilience,
            'reason' => 'task_completed',
        ]);
    }
}
