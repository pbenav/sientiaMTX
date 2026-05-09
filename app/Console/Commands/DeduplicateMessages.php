<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramMessage;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeduplicateMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:deduplicate {--apply : Aplicar los cambios y borrar en las redes sociales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chequear la base de datos en busca de mensajes duplicados de WhatsApp y Telegram, saneando la DB y borrándolos remotamente si es necesario.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apply = $this->option('apply');
        
        $this->info('========================================================');
        $this->info('🚀 INICIANDO SANEAMIENTO Y DEDUPLICACIÓN DE MENSAJES');
        $this->info('========================================================');
        if (!$apply) {
            $this->warn('⚠️  MODO DE SIMULACIÓN (DRY-RUN): No se realizarán borrados ni llamadas API. Usa --apply para aplicar.');
        }

        $this->deduplicateTelegram($apply);
        $this->deduplicateWhatsapp($apply);

        $this->info('========================================================');
        $this->info('✅ SANEAMIENTO COMPLETADO');
        $this->info('========================================================');
    }

    /**
     * Deduplica la tabla de telegram_messages usando ventana deslizante de tiempo inteligente
     */
    protected function deduplicateTelegram($apply)
    {
        $this->line('');
        $this->info('🔹 1. Analizando mensajes de Telegram...');

        // 1. Agrupar por team_id y text
        $textGroups = DB::table('telegram_messages')
            ->select('team_id', 'text', DB::raw('COUNT(*) as total'))
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->groupBy('team_id', 'text')
            ->having('total', '>', 1)
            ->get();

        $cleanedCount = 0;
        foreach ($textGroups as $group) {
            $clones = TelegramMessage::where('team_id', $group->team_id)
                ->where('text', $group->text)
                ->orderBy('created_at', 'asc')
                ->get();

            $processedIds = [];
            
            for ($i = 0; $i < count($clones); $i++) {
                $msg1 = $clones[$i];
                if (in_array($msg1->id, $processedIds)) continue;

                for ($j = $i + 1; $j < count($clones); $j++) {
                    $msg2 = $clones[$j];
                    if (in_array($msg2->id, $processedIds)) continue;

                    $diff = abs($msg2->created_at->diffInSeconds($msg1->created_at));
                    
                    // Si uno es sync_ y el otro es real numérico, ampliamos la ventana de coincidencia a 24 horas (86400s) para mitigar retrasos de webhook
                    $isSyncComparison = (str_starts_with($msg1->telegram_message_id ?? '', 'sync_') && !str_starts_with($msg2->telegram_message_id ?? '', 'sync_') && $msg2->telegram_message_id !== null)
                        || (str_starts_with($msg2->telegram_message_id ?? '', 'sync_') && !str_starts_with($msg1->telegram_message_id ?? '', 'sync_') && $msg1->telegram_message_id !== null);

                    $limit = $isSyncComparison ? 86400 : 45;

                    if ($diff <= $limit) {
                        $cleanedCount++;
                        
                        // Determinamos cuál conservar (preferimos el que tenga un ID de Telegram real numérico)
                        $keep = $msg1;
                        $delete = $msg2;

                        if (str_starts_with($msg1->telegram_message_id ?? '', 'sync_') && !str_starts_with($msg2->telegram_message_id ?? '', 'sync_') && $msg2->telegram_message_id !== null) {
                            $keep = $msg2;
                            $delete = $msg1;
                        }

                        $this->error("   [Telegram Duplicado] Encontrado clon (Diferencia: {$diff}s, Equipo: {$group->team_id})");
                        $this->line("      - Conservar ID: {$keep->id} (MsgID: {$keep->telegram_message_id})");
                        $this->line("      - Eliminar ID: {$delete->id} (MsgID: {$delete->telegram_message_id})");
                        $this->line("      Texto: '" . substr(strip_tags($keep->text), 0, 60) . "...'");

                        if ($apply) {
                            $delete->delete();
                            
                            // Si el borrado tiene ID real diferente, borrar remotamente
                            if ($delete->telegram_message_id && !str_starts_with($delete->telegram_message_id, 'sync_') && $delete->telegram_message_id !== $keep->telegram_message_id) {
                                $token = config('services.telegram.bot_token');
                                $chatId = $delete->team ? $delete->team->telegram_chat_id : null;
                                if ($token && $chatId) {
                                    try {
                                        Http::post("https://api.telegram.org/bot{$token}/deleteMessage", [
                                            'chat_id' => $chatId,
                                            'message_id' => $delete->telegram_message_id,
                                        ]);
                                        $this->info("         -> Borrado clon duplicado en Telegram de forma remota.");
                                    } catch (\Exception $e) {}
                                }
                            }
                            $this->info("         -> Registro duplicado borrado de la base de datos.");
                        }

                        $processedIds[] = $delete->id;
                        
                        if ($delete->id === $msg1->id) {
                            break;
                        }
                    }
                }
            }
        }

        // 2. Duplicados exactos de ID real numérico
        $idDuplicates = DB::table('telegram_messages')
            ->select('team_id', 'telegram_message_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('telegram_message_id')
            ->where('telegram_message_id', 'not like', 'sync_%')
            ->groupBy('team_id', 'telegram_message_id')
            ->having('total', '>', 1)
            ->get();

        $cleanedRealCount = 0;
        foreach ($idDuplicates as $dup) {
            $clones = TelegramMessage::where('team_id', $dup->team_id)
                ->where('telegram_message_id', $dup->telegram_message_id)
                ->orderBy('created_at', 'asc')
                ->get();

            $original = $clones->first();
            $clonesToDelete = $clones->slice(1);

            foreach ($clonesToDelete as $clone) {
                $cleanedRealCount++;
                $this->error("   [Telegram Duplicado ID] Encontrado ID duplicado (ID: {$clone->telegram_message_id}, Equipo: {$clone->team_id})");
                
                if ($apply) {
                    $clone->delete();
                    $token = config('services.telegram.bot_token');
                    $chatId = $clone->team ? $clone->team->telegram_chat_id : null;
                    if ($token && $chatId) {
                        try {
                            Http::post("https://api.telegram.org/bot{$token}/deleteMessage", [
                                'chat_id' => $chatId,
                                'message_id' => $clone->telegram_message_id,
                            ]);
                        } catch (\Exception $e) {}
                    }
                    $this->info("      -> Registro duplicado de ID borrado localmente.");
                }
            }
        }

        $this->info("   📊 Resumen Telegram: {$cleanedCount} duplicados procesados por tiempo, {$cleanedRealCount} duplicados por ID real.");
    }

    /**
     * Deduplica la tabla de whatsapp_messages usando ventana deslizante de tiempo inteligente
     */
    protected function deduplicateWhatsapp($apply)
    {
        $this->line('');
        $this->info('🔹 2. Analizando mensajes de WhatsApp...');

        // 1. Duplicados por ID de WhatsApp real
        $idDuplicates = DB::table('whatsapp_messages')
            ->select('team_id', 'message_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('message_id')
            ->where('message_id', '!=', '')
            ->groupBy('team_id', 'message_id')
            ->having('total', '>', 1)
            ->get();

        $cleanedRealCount = 0;
        foreach ($idDuplicates as $dup) {
            $clones = WhatsappMessage::where('team_id', $dup->team_id)
                ->where('message_id', $dup->message_id)
                ->orderBy('created_at', 'asc')
                ->get();

            $original = $clones->first();
            $clonesToDelete = $clones->slice(1);

            foreach ($clonesToDelete as $clone) {
                $cleanedRealCount++;
                $this->error("   [WhatsApp Duplicado ID] Encontrado ID duplicado (ID: {$clone->message_id}, Equipo: {$clone->team_id})");

                if ($apply) {
                    $clone->delete();
                    $session = $clone->team ? 'team_' . ($clone->team->slug ?: $clone->team->id) : 'default';
                    try {
                        Http::post('http://localhost:3001/api/delete', [
                            'session' => $session,
                            'message_id' => $clone->message_id,
                        ]);
                    } catch (\Exception $e) {}
                    $this->info("      -> Registro duplicado de ID borrado localmente.");
                }
            }
        }

        // 2. Duplicados por tiempo y texto (concurrencia, reenvío o retraso de sync)
        $textDuplicates = DB::table('whatsapp_messages')
            ->select('team_id', 'text', DB::raw('COUNT(*) as total'))
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->groupBy('team_id', 'text')
            ->having('total', '>', 1)
            ->get();

        $cleanedTextCount = 0;
        foreach ($textDuplicates as $dup) {
            $clones = WhatsappMessage::where('team_id', $dup->team_id)
                ->where('text', $dup->text)
                ->orderBy('created_at', 'asc')
                ->get();

            $processedIds = [];

            for ($i = 0; $i < count($clones); $i++) {
                $msg1 = $clones[$i];
                if (in_array($msg1->id, $processedIds)) continue;

                for ($j = $i + 1; $j < count($clones); $j++) {
                    $msg2 = $clones[$j];
                    if (in_array($msg2->id, $processedIds)) continue;

                    $diff = abs($msg2->created_at->diffInSeconds($msg1->created_at));
                    
                    // Si uno es sync_ (o vacío) y el otro es real, ampliamos el límite a 24 horas (86400s) para mitigar retrasos de sincronización
                    $isSyncComparison = (str_starts_with($msg1->message_id ?? '', 'sync_') && !str_starts_with($msg2->message_id ?? '', 'sync_') && !empty($msg2->message_id))
                        || (str_starts_with($msg2->message_id ?? '', 'sync_') && !str_starts_with($msg1->message_id ?? '', 'sync_') && !empty($msg1->message_id))
                        || (empty($msg1->message_id) && !empty($msg2->message_id))
                        || (empty($msg2->message_id) && !empty($msg1->message_id));

                    $limit = $isSyncComparison ? 86400 : 15;

                    if ($diff <= $limit) {
                        $cleanedTextCount++;

                        // Conservamos el que tenga un ID real (no nulo/vacío)
                        $keep = $msg1;
                        $delete = $msg2;

                        if (empty($msg1->message_id) && !empty($msg2->message_id)) {
                            $keep = $msg2;
                            $delete = $msg1;
                        }

                        $this->error("   [WhatsApp Duplicado] Encontrado clon (Diferencia: {$diff}s, Equipo: {$dup->team_id})");
                        $this->line("      - Conservar ID: {$keep->id} (MsgID: {$keep->message_id})");
                        $this->line("      - Eliminar ID: {$delete->id} (MsgID: {$delete->message_id})");
                        $this->line("      Texto: '" . substr(strip_tags($keep->text), 0, 60) . "...'");

                        if ($apply) {
                            $delete->delete();

                            if ($delete->message_id && $delete->message_id !== $keep->message_id) {
                                $session = $delete->team ? 'team_' . ($delete->team->slug ?: $delete->team->id) : 'default';
                                try {
                                    Http::post('http://localhost:3001/api/delete', [
                                        'session' => $session,
                                        'message_id' => $delete->message_id,
                                    ]);
                                } catch (\Exception $e) {}
                            }
                            $this->info("         -> Registro duplicado borrado de la base de datos.");
                        }

                        $processedIds[] = $delete->id;

                        if ($delete->id === $msg1->id) {
                            break;
                        }
                    }
                }
            }
        }

        $this->info("   📊 Resumen WhatsApp: Saneados {$cleanedRealCount} duplicados de ID, {$cleanedTextCount} clones por tiempo.");
    }
}
