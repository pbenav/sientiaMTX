<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;

class SyncTaskAssignmentModes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sientia:sync-task-modes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los modos de asignación (shared/distributed) en las actividades heredadas según si son plantillas o no.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de assignment_mode para actividades de tipo Task...');

        $activities = Activity::where('type', 'task')->get();
        $updatedDistributed = 0;
        $updatedShared = 0;
        $updatedInstances = 0;

        foreach ($activities as $activity) {
            $metadata = $activity->metadata ?? [];
            $changed = false;

            // Si es un Plan Maestro (is_template = true), debe ser distributed.
            if ($activity->is_template) {
                if (!isset($metadata['assignment_mode']) || $metadata['assignment_mode'] !== 'distributed') {
                    $metadata['assignment_mode'] = 'distributed';
                    $changed = true;
                    $updatedDistributed++;
                }
            } 
            // Si es una instancia distribuida (es hija de una plantilla)
            elseif (!$activity->is_template && $activity->parent_id) {
                // Verificamos si el padre es plantilla
                $parent = Activity::find($activity->parent_id);
                if ($parent && $parent->is_template) {
                    if (!isset($metadata['assignment_mode']) || $metadata['assignment_mode'] !== 'shared') {
                        $metadata['assignment_mode'] = 'shared';
                        $metadata['is_distributed_instance'] = true;
                        $changed = true;
                        $updatedInstances++;
                    }
                } else {
                    if (!isset($metadata['assignment_mode']) || $metadata['assignment_mode'] !== 'shared') {
                        $metadata['assignment_mode'] = 'shared';
                        $changed = true;
                        $updatedShared++;
                    }
                }
            }
            // Si es una Tarea Compartida o Normal
            else {
                if (!isset($metadata['assignment_mode']) || $metadata['assignment_mode'] !== 'shared') {
                    $metadata['assignment_mode'] = 'shared';
                    $changed = true;
                    $updatedShared++;
                }
            }

            if ($changed) {
                // Deshabilitamos eventos para que no modifique nada más (notificaciones, etc.)
                Activity::withoutEvents(function() use ($activity, $metadata) {
                    $activity->metadata = $metadata;
                    $activity->save();
                });
            }
        }

        $this->info('¡Sincronización completada!');
        $this->line("Planes Maestros (Distributed) actualizados: <info>$updatedDistributed</info>");
        $this->line("Instancias de Plantilla actualizadas: <info>$updatedInstances</info>");
        $this->line("Tareas Compartidas (Shared) actualizadas: <info>$updatedShared</info>");
    }
}
