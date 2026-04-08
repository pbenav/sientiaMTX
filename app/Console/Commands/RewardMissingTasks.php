<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\GamificationLog;
use App\Models\User;
use App\Traits\AwardsGamification;

class RewardMissingTasks extends Command
{
    use AwardsGamification;

    protected $signature = 'gamification:reward-missing';
    protected $description = 'Otorga puntos de gamificación a tareas completadas que no tienen registro.';

    public function handle()
    {
        $this->info('Buscando tareas completadas sin registro de gamificación...');

        // Buscamos tareas en estado 'completed' que no tengan una entrada en gamification_logs
        // donde source_type sea Task y source_id sea el id de la tarea.
        $tasks = Task::where('status', 'completed')
            ->whereDoesntHave('histories', function($q) {
                // Alternativamente, podemos mirar gamification_logs directamente
            })
            ->get();
            
        // Es mejor mirar gamification_logs directamente
        $completedTaskIds = Task::where('status', 'completed')->pluck('id')->toArray();
        $loggedTaskIds = GamificationLog::where('source_type', 'App\Models\Task')
            ->whereIn('source_id', $completedTaskIds)
            ->pluck('source_id')
            ->toArray();
            
        $missingTaskIds = array_diff($completedTaskIds, $loggedTaskIds);
        
        if (empty($missingTaskIds)) {
            $this->info('No se encontraron tareas pendientes de recompensa.');
            return 0;
        }

        $this->info('Otorgando recompensas para ' . count($missingTaskIds) . ' tareas...');

        $tasksToReward = Task::whereIn('id', $missingTaskIds)->with(['skills', 'assignedUser'])->get();
        $count = 0;

        foreach ($tasksToReward as $task) {
            $this->line("Procesando: {$task->title} (ID: {$task->id})");
            $this->awardGamificationPoints($task);
            $count++;
        }

        $this->info("¡Completado! Se han recompensado {$count} tareas.");
        return 0;
    }
}
