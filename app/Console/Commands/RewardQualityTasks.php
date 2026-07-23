<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Traits\AwardsGamification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Procesa tareas vencidas sin recompensa de calidad y otorga bonificaciones XP a sus creadores.
 *
 * Identifica tareas cuya fecha de vencimiento ya pasó y que no han sido procesadas
 * para recompensa de calidad, calcula el promedio de puntuación de calificaciones y
 * otorga bonificaciones de experiencia mediante el trait AwardsGamification.
 *
 * # Ejecución
 * ```bash
 * php artisan task:reward-quality
 * ```
 *
 * @author  SientiaMTX Team
 * @version 1.0.0
 */
class RewardQualityTasks extends Command
{
    use AwardsGamification;

    /**
     * Firma del comando.
     */
    protected $signature = 'task:reward-quality';

    /**
     * Descripción del comando.
     */
    protected $description = 'Processes past-due tasks and awards XP bonus to creators based on quality ratings.';

    /**
     * Punto de entrada principal del comando.
     *
     * Encuentra tareas vencidas sin recompensa de calidad emitida, calcula el promedio
     * de calificaciones para cada una, aplica la bonificación de calidad del trait
     * AwardsGamification y marca las tareas como procesadas para evitar reevaluaciones.
     * Cada tarea se procesa dentro de una transacción atómica.
     *
     * @return int Código de salida del comando (SUCCESS).
     */
    public function handle()
    {
        $now = now();
        $this->info("Processing task quality rewards...");

        // Find tasks that passed their due date and haven't had reward processed yet
        $tasks = Task::where('quality_reward_issued', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $now)
            ->with(['creator', 'ratings'])
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            DB::transaction(function () use ($task, &$count) {
                // Recalculate final average just in case cache is stale
                $avgScore = $task->ratings()->avg('score') ?: 0;
                
                if ($avgScore > 0) {
                    // Sync finalized cache
                    $task->avg_quality_score = $avgScore;
                    
                    // Issue the reward logic stored in Trait
                    $this->awardTaskQualityBonus($task, $avgScore);
                }

                // Mark as processed to prevent duplicate evaluations
                $task->quality_reward_issued = true;
                $task->saveQuietly();
                
                $count++;
            });
        }

        $this->info("Processed {$count} tasks for potential quality rewards.");
        return Command::SUCCESS;
    }
}
