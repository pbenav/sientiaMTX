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
     * Deduplica la tabla de telegram_messages
     */
    protected function deduplicateTelegram($apply)
    {
        $this->line('');
        $this->info('🔹 1. Analizando mensajes de Telegram...');

        // CASO A: Duplicados por id temporal 'sync_...' vs id real de Telegram (Mismo texto, mismo equipo, creado con < 45s de diferencia)
        $syncDuplicates = TelegramMessage::where('telegram_message_id', 'like', 'sync_%')
            ->get();

        $cleanedSyncCount = 0;
        foreach ($syncDuplicates as $syncMsg) {
            // Buscamos si existe un clon con id real (no sync_) para el mismo equipo y texto similar, creado al mismo tiempo (+/- 45s)
            $realClon = TelegramMessage::where('team_id', $syncMsg->team_id)
                ->where('telegram_message_id', 'not like', 'sync_%')
                ->whereBetween('created_at', [
                    $syncMsg->created_at->subSeconds(45),
                    $syncMsg->created_at->addSeconds(45)
                ])
                ->get()
                ->first(function ($msg) use ($syncMsg) {
                    return trim(strip_tags($msg->text)) === trim(strip_tags($syncMsg->text))
                        || str_contains($msg->text, $syncMsg->text)
                        || str_contains($syncMsg->text, $msg->text);
                });

            if ($realClon) {
                $cleanedSyncCount++;
                $this->warn("   [Clon Local] Encontrado registro sync '{$syncMsg->telegram_message_id}' duplicado con real '{$realClon->telegram_message_id}' (Equipo: {$syncMsg->team_id})");
                $this->line("      Texto: '" . substr(strip_tags($syncMsg->text), 0, 50) . "...'");
                
                if ($apply) {
                    // Borramos únicamente el registro local 'sync' de la DB (el real se queda intacto en local y en Telegram, por lo que no llamamos a Telegram API)
                    $syncMsg->delete();
                    $this->info("      -> Registro duplicado local 'sync_{$syncMsg->id}' borrado con éxito de la DB (Telegram intacto).");
                }
            }
        }

        // CASO B: Duplicados reales de ID (Mismo telegram_message_id y mismo team_id)
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
                $this->error("   [Duplicado Externo] Encontrado mensaje Telegram real duplicado (ID: {$clone->telegram_message_id}, Equipo: {$clone->team_id})");
                
                if ($apply) {
                    // 1. Borrar en la DB local
                    $clone->delete();
                    
                    // 2. Borrar en la red social Telegram (como es un clon duplicado real en el grupo, borramos el clon en Telegram también para mantener coherencia)
                    $token = config('services.telegram.bot_token');
                    $chatId = $clone->team ? $clone->team->telegram_chat_id : null;
                    if ($token && $chatId && $clone->telegram_message_id) {
                        try {
                            Http::post("https://api.telegram.org/bot{$token}/deleteMessage", [
                                'chat_id' => $chatId,
                                'message_id' => $clone->telegram_message_id,
                            ]);
                            $this->info("      -> Borrado de Telegram de forma remota.");
                        } catch (\Exception $e) {
                            $this->error("      -> Error al borrar de Telegram: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->info("   📊 Resumen Telegram: {$cleanedSyncCount} duplicados locales de sincronización procesados, {$cleanedRealCount} duplicados externos reales saneados.");
    }

    /**
     * Deduplica la tabla de whatsapp_messages
     */
    protected function deduplicateWhatsapp($apply)
    {
        $this->line('');
        $this->info('🔹 2. Analizando mensajes de WhatsApp...');

        // CASO A: Duplicados reales de ID (Mismo message_id y mismo team_id)
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
                $this->error("   [Duplicado Externo] Encontrado mensaje WhatsApp real duplicado (ID: {$clone->message_id}, Equipo: {$clone->team_id})");

                if ($apply) {
                    // 1. Borrar localmente de la DB
                    $clone->delete();

                    // 2. Borrar de forma remota en WhatsApp para mantener coherencia
                    $session = $clone->team ? 'team_' . ($clone->team->slug ?: $clone->team->id) : 'default';
                    try {
                        $response = Http::post('http://localhost:3001/api/delete', [
                            'session' => $session,
                            'message_id' => $clone->message_id,
                        ]);
                        if ($response->successful()) {
                            $this->info("      -> Borrado de WhatsApp de forma remota para la sesión '{$session}'.");
                        } else {
                            // Intentar con default
                            Http::post('http://localhost:3001/api/delete', [
                                'session' => 'default',
                                'message_id' => $clone->message_id,
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->error("      -> Error al borrar de WhatsApp: " . $e->getMessage());
                    }
                }
            }
        }

        // CASO B: Duplicados sin ID o creados por colisión de concurrencia (Mismo texto, mismo equipo, creado con < 15s de diferencia)
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

            $original = $clones->first();
            $clonesToDelete = $clones->slice(1);

            foreach ($clonesToDelete as $clone) {
                // Verificar si fueron creados con menos de 15s de diferencia
                $diff = abs($clone->created_at->diffInSeconds($original->created_at));
                if ($diff <= 15) {
                    $cleanedTextCount++;
                    $this->warn("   [Clon Local] Encontrado mensaje WhatsApp duplicado por tiempo de envío (Diferencia: {$diff}s, Equipo: {$clone->team_id})");
                    $this->line("      Texto: '" . substr(strip_tags($clone->text), 0, 50) . "...'");

                    if ($apply) {
                        $clone->delete();
                        $this->info("      -> Registro duplicado de texto borrado localmente de la DB.");
                        
                        // Si tiene un ID real diferente, también lo borramos de WhatsApp remotamente
                        if ($clone->message_id && $clone->message_id !== $original->message_id) {
                            $session = $clone->team ? 'team_' . ($clone->team->slug ?: $clone->team->id) : 'default';
                            try {
                                Http::post('http://localhost:3001/api/delete', [
                                    'session' => $session,
                                    'message_id' => $clone->message_id,
                                ]);
                                $this->info("      -> Borrado clon duplicado en WhatsApp de forma remota.");
                            } catch (\Exception $e) {}
                        }
                    }
                }
            }
        }

        $this->info("   📊 Resumen WhatsApp: Saneados {$cleanedRealCount} duplicados de ID y {$cleanedTextCount} clones por tiempo.");
    }
}
