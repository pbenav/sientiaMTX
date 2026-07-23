<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use Carbon\Carbon;

/**
 * Genera ocurrencias futuras para tareas autoprogramables cuando se alcanza el umbral de preaviso.
 *
 * Recorre todas las tareas marcadas como autoprogramables, evalúa si la próxima ocurrencia
 * está dentro del período de anticipación configurado y las "despierta" para generar
 * las instancias correspondientes. Opcionalmente permite limpiar ocurrencias futuras
 * redundantes del modelo anterior.
 *
 * # Ejecución
 * ```bash
 * php artisan app:tasks-autoprogram-wakeup
 * php artisan app:tasks-autoprogram-wakeup --cleanup
 * ```
 *
 * @author  SientiaMTX Team
 * @version 1.0.0
 */
class WakeupAutoprogrammedTasks extends Command
{
    /**
     * Firma del comando con opción opcional de limpieza.
     *
     * --cleanup : Elimina ocurrencias futuras redundantes de tareas maestras autoprogramables.
     */
    protected $signature = 'app:tasks-autoprogram-wakeup {--cleanup : Realizar limpieza de ejecuciones futuras previas}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Procesa las tareas autoprogramables para generar la siguiente ocurrencia si está dentro del preaviso definido.';

    /**
     * Punto de entrada principal del comando.
     *
     * Si se proporciona la bandera --cleanup, ejecuta la limpieza de ocurrencias futuras.
     * Luego itera sobre las tareas autoprogramables y genera sus próximas ocurrencias
     * cuando se cumple el umbral de anticipación definido en los ajustes de autoprogramación.
     * Incluye una salvaguarda de 5 iteraciones máximas para evitar bucles infinitos.
     *
     * @return int Código de salida del comando (SUCCESS).
     */
    public function handle()
    {
        if ($this->option('cleanup')) {
            $this->info('Iniciando limpieza de ocurrencias futuras redundantes...');
            $this->cleanupFutureOccurrences();
        }

        $tasks = Task::where('is_autoprogrammable', true)->get();

        foreach ($tasks as $task) {
            $settings = $task->autoprogram_settings;
            $leadValue = (int)($settings['lead_value'] ?? 7);
            $leadUnit = $settings['lead_unit'] ?? 'days';

            // Generamos todas las ocurrencias que entren en el umbral de preaviso
            // Generamos todas las ocurrencias que entren en el umbral de preaviso
            // de forma iterativa en una sola ejecución. Con salvaguarda para evitar bucles infinitos.
            $safetyCounter = 0;
            while ($task->is_autoprogrammable) {
                if ($safetyCounter >= 5) {
                    $this->warn("  [Salvaguarda] Se ha alcanzado el límite de 5 iteraciones consecutivas para '{$task->title}' para evitar bucles infinitos de CPU.");
                    break;
                }
                $safetyCounter++;

                $settings = $task->autoprogram_settings;
                $nextAt = isset($settings['next_occurrence_at']) ? Carbon::parse($settings['next_occurrence_at']) : ($task->scheduled_date ? Carbon::parse($task->scheduled_date) : now());
                
                $wakeupThreshold = $nextAt->copy();
                switch ($leadUnit) {
                    case 'hours': $wakeupThreshold->subHours($leadValue); break;
                    case 'days': $wakeupThreshold->subDays($leadValue); break;
                    case 'weeks': $wakeupThreshold->subWeeks($leadValue); break;
                    case 'months': $wakeupThreshold->subMonths($leadValue); break;
                    default: $wakeupThreshold->subDays($leadValue);
                }

                if (now()->greaterThanOrEqualTo($wakeupThreshold)) {
                    $this->info("Despertando tarea: {$task->title} para la fecha {$nextAt->toDateString()}");
                    $task->generateOccurrences();
                    $task->refresh(); // Refrescamos el modelo para obtener el nuevo next_occurrence_at
                } else {
                    break; // Salimos del bucle si la siguiente ya no entra en el umbral
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Elimina ocurrencias futuras de tareas autoprogramables maestras.
     *
     * Busca tareas maestras que tengan hijos generados y elimina aquellos cuya
     * fecha de ejecución esté programada para después del día actual y cuyo estado
     * sea pendiente. Esto permite resetear el sistema al nuevo modelo JIT.
     *
     * @return void
     */
    protected function cleanupFutureOccurrences()
    {
        // Buscamos tareas que tienen hijos generados pero que son autoprogramables (maestras)
        $masters = Task::where('is_autoprogrammable', true)->has('children')->get();

        foreach ($masters as $master) {
            // Eliminamos todos los hijos que estén programados a partir de mañana
            // Esto permite resetear el sistema al nuevo modelo JIT
            $deletedCount = $master->children()
                ->where('metadata->is_occurrence', true) // SOLO borramos lo que el sistema generó
                ->where('scheduled_date', '>', now()->endOfDay())
                ->where('status', 'pending')
                ->get()
                ->each
                ->delete()
                ->count();
            
            if ($deletedCount > 0) {
                $this->line("  - {$master->title}: Eliminadas {$deletedCount} ocurrencias futuras.");
            }
        }
    }
}
