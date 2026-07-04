<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PruneDeprecatedActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:prune-deprecated 
                            {--days=90 : Número de días que deben haber pasado desde la conversión}
                            {--force : Fuerza el borrado sin preguntar (necesario en producción o cron)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Borra de forma definitiva las actividades deprecadas por conversión que excedan el tiempo de retención.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $thresholdDate = Carbon::now()->subDays($days);

        $this->info("Buscando actividades deprecadas por conversión antes de {$thresholdDate->toDateString()}...");

        // UsamoswithTrashed por si acaso estaban soft-deleted, pero nos interesa el borrado definitivo.
        $query = Activity::withTrashed()
            ->where('is_archived', true)
            ->where(function ($q) {
                // Estado explícito deprecado
                $q->where('status->value', 'deprecated')
                  // O metadato de deprecación
                  ->orWhere('metadata->is_deprecated', true);
            })
            // Que la fecha de conversión (o actualización si no hay fecha exacta) sea anterior al límite
            ->where(function ($q) use ($thresholdDate) {
                $q->where('status->converted_at', '<', $thresholdDate)
                  ->orWhere('updated_at', '<', $thresholdDate);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No se encontraron actividades deprecadas antiguas para purgar.');
            return Command::SUCCESS;
        }

        $this->warn("Se han encontrado {$count} actividades deprecadas antiguas.");

        if (!$force) {
            if (!$this->confirm('¿Deseas borrarlas DEFINITIVAMENTE (Hard Delete)? Esta acción no se puede deshacer.')) {
                $this->info('Operación cancelada por el usuario.');
                return Command::SUCCESS;
            }
        }

        $this->info('Purgando actividades...');

        $deletedCount = 0;
        
        // Lo hacemos en chunks para no saturar la memoria y disparar eventos si hay adjuntos
        $query->chunkById(100, function ($activities) use (&$deletedCount) {
            foreach ($activities as $activity) {
                // forceDelete dispara el evento 'deleting' que puede limpiar adjuntos u otras relaciones en cascada
                $activity->forceDelete();
                $deletedCount++;
            }
        });

        Log::info("Purgadas {$deletedCount} actividades deprecadas (más de {$days} días).");
        $this->info("¡Purga completada! Se han eliminado definitivamente {$deletedCount} actividades.");

        return Command::SUCCESS;
    }
}
