<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatMessage;
use App\Models\Team;
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
                            {--days= : Override global: Purgar todos los mensajes de más de X días (ignora preferencias de equipo)} 
                            {--force : Ejecutar sin solicitar confirmación (útil para cron/scheduler)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina los mensajes de chat antiguos y sus archivos adjuntos asociados según las preferencias de retención de cada equipo de trabajo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $overrideDays = $this->option('days');
        
        if ($overrideDays !== null) {
            $days = (int) $overrideDays;
            if ($days <= 0) {
                $this->error('El número de días de override debe ser mayor que cero.');
                return 1;
            }
            return $this->purgeGlobally($days);
        }

        return $this->purgeByTeamSettings();
    }

    /**
     * Purgar mensajes aplicando las políticas configuradas en cada equipo de trabajo
     */
    protected function purgeByTeamSettings()
    {
        $this->info("Iniciando purga de mensajes de chat según preferencias de cada equipo...");
        Log::info("ChatPurge: Iniciando purga automática según preferencias de equipo.");

        $teams = Team::all();
        $totalPurged = 0;
        $totalFilesDeleted = 0;

        foreach ($teams as $team) {
            $retentionDays = (int) ($team->settings['chat_retention_days'] ?? 0);
            if ($retentionDays <= 0) {
                $this->info("Equipo '{$team->name}': Sin límite de retención (conservar para siempre).");
                continue;
            }

            $cutOffDate = now()->subDays($retentionDays);
            $memberIds = $team->members()->pluck('users.id')->toArray();

            if (empty($memberIds)) {
                continue;
            }

            // 1. Mensajes directos entre miembros de este equipo
            $directMessagesQuery = ChatMessage::whereNull('chat_group_id')
                ->whereIn('sender_id', $memberIds)
                ->whereIn('receiver_id', $memberIds)
                ->where('created_at', '<', $cutOffDate);

            // 2. Mensajes de grupos creados por miembros de este equipo
            $groupMessagesQuery = ChatMessage::whereNotNull('chat_group_id')
                ->whereHas('group', function ($q) use ($memberIds) {
                    $q->whereIn('created_by', $memberIds);
                })
                ->where('created_at', '<', $cutOffDate);

            // Combinar los IDs de mensajes a eliminar para este equipo
            $messageIds = $directMessagesQuery->pluck('id')
                ->merge($groupMessagesQuery->pluck('id'))
                ->unique()
                ->toArray();

            $count = count($messageIds);
            if ($count === 0) {
                $this->info("Equipo '{$team->name}': No hay mensajes anteriores a {$cutOffDate->format('Y-m-d')} (retención de {$retentionDays} días).");
                continue;
            }

            $this->info("Equipo '{$team->name}': Purgando {$count} mensajes (anteriores a {$cutOffDate->format('Y-m-d')})...");

            $purgedCount = 0;
            $filesCount = 0;

            // Procesar en chunks por ID
            ChatMessage::whereIn('id', $messageIds)->chunkById(100, function ($messages) use (&$purgedCount, &$filesCount) {
                foreach ($messages as $message) {
                    if ($message->file_path) {
                        try {
                            if (Storage::exists($message->file_path)) {
                                Storage::delete($message->file_path);
                                $filesCount++;
                            }
                        } catch (\Throwable $e) {
                            Log::error("Error al borrar archivo adjunto del chat (Mensaje ID: {$message->id}): " . $e->getMessage());
                        }
                    }
                    $message->delete();
                    $purgedCount++;
                }
            });

            $totalPurged += $purgedCount;
            $totalFilesDeleted += $filesCount;
        }

        $this->info("Proceso completado:");
        $this->info("- Total mensajes eliminados: {$totalPurged}");
        $this->info("- Total archivos adjuntos eliminados: {$totalFilesDeleted}");
        
        Log::info("ChatPurge: Finalizada purga de equipos. Mensajes eliminados: {$totalPurged}, Archivos: {$totalFilesDeleted}");
        return 0;
    }

    /**
     * Purgar de forma global (override de todos los mensajes sin importar el equipo)
     */
    protected function purgeGlobally(int $days)
    {
        $cutOffDate = now()->subDays($days);
        $query = ChatMessage::where('created_at', '<', $cutOffDate);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info("No hay mensajes de chat anteriores al {$cutOffDate->format('Y-m-d H:i:s')} (más de {$days} días).");
            return 0;
        }

        if (!$this->option('force')) {
            $confirm = $this->confirm(
                "¿Estás seguro de que deseas purgar GLOBALMENTE {$totalCount} mensajes de chat antiguos anteriores a {$cutOffDate->format('Y-m-d')}? Esta acción es irreversible y eliminará los archivos adjuntos físicamente.",
                false
            );

            if (!$confirm) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $this->info("Iniciando purga global de {$totalCount} mensajes...");
        
        $purgedCount = 0;
        $filesDeletedCount = 0;

        $query->chunkById(100, function ($messages) use (&$purgedCount, &$filesDeletedCount) {
            foreach ($messages as $message) {
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
                $message->delete();
                $purgedCount++;
            }
        });

        $this->info("Proceso global completado con éxito:");
        $this->info("- Mensajes eliminados: {$purgedCount}");
        $this->info("- Archivos adjuntos eliminados: {$filesDeletedCount}");

        Log::info("ChatPurge: Purga global completada. Mensajes: {$purgedCount}, Archivos: {$filesDeletedCount}");
        return 0;
    }
}
