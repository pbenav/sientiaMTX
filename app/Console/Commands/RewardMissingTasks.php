<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\GamificationLog;
use App\Models\User;
use App\Traits\AwardsGamification;

/**
 * Otorga puntos de gamificación a tareas completadas que carecen de registro en el sistema.
 *
 * Compara las tareas con estado 'completed' contra los registros existentes en
 * gamification_logs y otorga las recompensas correspondientes a aquellas que no
 * tengan un registro asociado, utilizando el trait AwardsGamification.
 *
 * # Ejecución
 * ```bash
 * php artisan gamification:reward-missing
 * ```
 *
 * @author  SientiaMTX Team
 * @version 1.0.0
 */
class RewardMissingTasks extends Command
{
    use AwardsGamification;

    /**
     * Firma del comando.
     */
    protected $signature = 'gamification:reward-missing';

    /**
     * Descripción del comando.
     */
    protected $description = 'Otorga puntos de gamificación a tareas completadas que no tienen registro.';

    /**
     * Punto de entrada principal del comando.
     *
     * Identifica tareas completadas sin registro en gamification_logs, itera sobre
     * cada una aplicando la lógica de recompensa del trait AwardsGamification y
     * reporta el total de tareas recompensadas.
     *
     * @return int 0 en caso de éxito.
     */
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
