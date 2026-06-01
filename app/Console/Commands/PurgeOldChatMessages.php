<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PurgeOldChatMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:purge-old-messages 
                            {--days=30 : Los días de antigüedad para purgar mensajes} 
                            {--force : Ejecutar sin solicitar confirmación (útil para cron/scheduler)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina los mensajes de chat antiguos y sus archivos adjuntos asociados para liberar espacio y agilizar la base de datos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('El número de días debe ser un entero positivo mayor que cero.');
            return 1;
        }

        $cutOffDate = now()->subDays($days);
        
        $query = ChatMessage::where('created_at', '<', $cutOffDate);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info("No hay mensajes de chat anteriores al {$cutOffDate->format('Y-m-d H:i:s')} (más de {$days} días).");
            return 0;
        }

        if (!$this->option('force')) {
            $confirm = $this->confirm(
                "¿Estás seguro de que deseas purgar {$totalCount} mensajes de chat antiguos anteriores a {$cutOffDate->format('Y-m-d')}? Esta acción es irreversible y eliminará los archivos adjuntos físicamente.",
                false
            );

            if (!$confirm) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $this->info("Iniciando purga de {$totalCount} mensajes...");
        
        $purgedCount = 0;
        $filesDeletedCount = 0;

        // Procesar en lotes (chunks) para eficiencia y evitar sobrecargar la memoria
        $query->chunkById(100, function ($messages) use (&$purgedCount, &$filesDeletedCount) {
            foreach ($messages as $message) {
                // Si tiene archivo adjunto en almacenamiento local/nube, eliminarlo
                if ($message->file_path) {
                    try {
                        if (Storage::exists($message->file_path)) {
                            Storage::delete($message->file_path);
                            $filesDeletedCount++;
                        }
                    } catch (\Throwable $e) {
                        Log::error("Error al borrar archivo adjunto del chat (Mensaje ID: {$message->id}): " . $e->getMessage());
                    }
                }
                
                // Borrar el registro del mensaje
                $message->delete();
                $purgedCount++;
            }
        });

        $this->info("Proceso completado con éxito:");
        $this->info("- Mensajes eliminados de la base de datos: {$purgedCount}");
        $this->info("- Archivos adjuntos borrados físicamente: {$filesDeletedCount}");

        Log::info("ChatPurge: Purga automática de mensajes completada. Mensajes eliminados: {$purgedCount}, Archivos borrados: {$filesDeletedCount}");

        return 0;
    }
}
