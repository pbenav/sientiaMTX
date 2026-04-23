<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class SanitizeTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:sanitize {--fix : Ejecutar las correcciones automáticamente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia inconsistencias históricas y datos "paticojos" en las tareas (asignaciones erróneas, huérfanos, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🧹 Iniciando desinfección de tareas históricas...");
        $fix = $this->option('fix');

        $this->cleanTemplateCollaborators($fix);
        $this->cleanOrphanedAssignments($fix);

        $this->info("");
        $this->info("✅ Desinfección finalizada.");
        return Command::SUCCESS;
    }

    /**
     * 1. Elimina colaboradores (pivot) de los Planes Maestros.
     * Los Planes Maestros son plantillas y no deberían tener asignaciones de ejecución.
     */
    protected function cleanTemplateCollaborators($fix)
    {
        $this->warn("\n1. Buscando asignaciones de colaboradores en Planes Maestros (Plantillas)...");
        
        $badAssignments = DB::table('task_assignments')
            ->join('tasks', 'tasks.id', '=', 'task_assignments.task_id')
            ->where('tasks.is_template', true)
            ->whereNotNull('task_assignments.user_id')
            ->select('task_assignments.*', 'tasks.title as task_title')
            ->get();

        if ($badAssignments->isEmpty()) {
            $this->line("   - No se encontraron asignaciones erróneas en plantillas.");
            return;
        }

        $count = $badAssignments->count();
        $this->error("   - Se han encontrado {$count} asignaciones erróneas en Planes Maestros.");

        foreach ($badAssignments as $as) {
            $this->line("     -> Tarea #{$as->task_id} [{$as->task_title}] tiene asignado al Usuario #{$as->user_id}");
        }

        if ($fix) {
            $ids = $badAssignments->pluck('id')->toArray();
            DB::table('task_assignments')->whereIn('id', $ids)->delete();
            $this->info("     [FIXED] {$count} asignaciones eliminadas correctamente.");
        } else {
            $this->info("     [INFO] Usa --fix para eliminar estas asignaciones.");
        }
    }

    /**
     * 2. Elimina asignaciones que apuntan a tareas o usuarios que ya no existen.
     */
    protected function cleanOrphanedAssignments($fix)
    {
        $this->warn("\n2. Buscando asignaciones huérfanas (tareas o usuarios inexistentes)...");

        $orphans = DB::table('task_assignments')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tasks')
                    ->whereColumn('tasks.id', 'task_assignments.task_id');
            })
            ->orWhere(function($q) {
                $q->whereNotNull('user_id')
                  ->whereNotExists(function ($query) {
                      $query->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'task_assignments.user_id');
                  });
            })
            ->get();

        if ($orphans->isEmpty()) {
            $this->line("   - No hay asignaciones huérfanas.");
            return;
        }

        $count = $orphans->count();
        $this->error("   - Se han encontrado {$count} asignaciones huérfanas.");

        if ($fix) {
            $ids = $orphans->pluck('id')->toArray();
            DB::table('task_assignments')->whereIn('id', $ids)->delete();
            $this->info("     [FIXED] {$count} asignaciones huérfanas eliminadas.");
        }
    }
}
