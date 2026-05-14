<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class PurgeSoftDeletedTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:purge {--force : Saltarse la confirmación interactiva}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina permanentemente todos los equipos eliminados (soft-deleted) y purga sus archivos y datos relacionados.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedTeams = Team::onlyTrashed()->get();

        if ($deletedTeams->isEmpty()) {
            $this->info('No hay equipos eliminados pendientes de purga.');
            return 0;
        }

        $this->warn("Se han encontrado {$deletedTeams->count()} equipos en la papelera:");
        foreach ($deletedTeams as $team) {
            $this->line("- {$team->name} (ID: {$team->id}, Slug: {$team->slug})");
        }

        if (!$this->option('force') && !$this->confirm('¿Estás seguro de que quieres eliminar PERMANENTEMENTE estos equipos y todos sus datos asociados?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->info('Iniciando purga profunda...');

        foreach ($deletedTeams as $team) {
            $this->line("Purgando equipo: {$team->name}...");
            
            // forceDelete() disparará el listener booted() en el modelo Team que realiza la limpieza profunda
            $team->forceDelete();
            
            $this->info("¡Equipo {$team->id} purgado con éxito!");
        }

        $this->info('Purga completada con éxito.');
        return 0;
    }
}
