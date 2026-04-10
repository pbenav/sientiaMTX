<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskHistory;
use App\Models\TaskTag;
use App\Models\TimeLog;
use App\Models\ForumThread;
use App\Traits\ManagesTaskDeletion;
use Illuminate\Support\Facades\DB;

class CleanupTasks extends Command
{
    use ManagesTaskDeletion;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tasks:cleanup-trash 
                            {--fix-hierarchy : Trashear automáticamente hijos de tareas ya trasheadas}
                            {--force : Eliminar PERMANENTEMENTE los registros de la papelera}
                            {--dry-run : Mostrar lo que se haría sin ejecutar cambios}';

    /**
     * The console command description.
     */
    protected $description = 'Limpia la base de datos de tareas eliminadas y soluciona inconsistencias de jerarquía.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🧹 Iniciando limpieza de papelera de tareas...");
        
        if ($this->option('dry-run')) {
            $this->warn("⚠️ MODO SIMULACIÓN (Dry Run): No se realizarán cambios reales.");
        }

        if ($this->option('fix-hierarchy')) {
            $this->fixHierarchy();
        }

        if ($this->option('force')) {
            $this->purgeTrash();
        } else {
            $this->showTrashSummary();
        }

        $this->info("✅ Proceso finalizado.");
        return Command::SUCCESS;
    }

    /**
     * Fix hierarchy inconsistencies: any child of a trashed task should also be trashed.
     */
    protected function fixHierarchy()
    {
        $this->warn("\n1. Reparando jerarquía (cascada de borrado lógico)...");
        
        // Find tasks that are not trashed but their parent IS trashed
        $orphanedActive = Task::whereNull('deleted_at')
            ->whereExists(function ($query) {
                $query->select('*')
                    ->from('tasks as parents')
                    ->whereColumn('parents.id', 'tasks.parent_id')
                    ->whereNotNull('parents.deleted_at');
            })->get();

        if ($orphanedActive->isEmpty()) {
            $this->line("   - No se encontraron inconsistencias de jerarquía.");
            return;
        }

        $this->error("   - Encontradas " . $orphanedActive->count() . " tareas activas con padres eliminados.");

        foreach ($orphanedActive as $task) {
            $this->line("     -> Trasheando tarea #{$task->id}: {$task->title}");
            if (!$this->option('dry-run')) {
                $task->delete();
            }
        }
        
        // Recursive check: hidden children might have children of their own
        if (!$this->option('dry-run')) {
            $this->fixHierarchy();
        }
    }

    /**
     * Show a summary of items currently in the trash.
     */
    protected function showTrashSummary()
    {
        $count = Task::onlyTrashed()->count();
        $this->warn("\n2. Resumen de papelera:");
        $this->line("   - Tareas en papelera: {$count}");
        
        if ($count > 0) {
            $this->info("   -> Usa --force para eliminar permanentemente estos registros.");
        }
    }

    /**
     * Permanently delete trashed tasks and all related records.
     */
    protected function purgeTrash()
    {
        $trashedTasks = Task::onlyTrashed()->get();
        $count = $trashedTasks->count();

        if ($count === 0) {
            $this->line("   - No hay tareas en la papelera para purgar.");
            return;
        }

        $this->warn("\n🔥 Purgando {$count} tareas y sus registros asociados...");

        if (!$this->option('dry-run') && !$this->confirm("¿Estás SEGURO de querer eliminar PERMANENTEMENTE estas {$count} tareas? Esta acción no se puede deshacer.", false)) {
            $this->info("   - Operación cancelada por el usuario.");
            return;
        }

        foreach ($trashedTasks as $task) {
            $this->line("     -> Purgando permanentemente tarea #{$task->id}: {$task->title}");
            if (!$this->option('dry-run')) {
                $this->deepPurgeTask($task);
            }
        }

        $this->info("   - Purga completada.");
    }
}
