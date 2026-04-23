<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura el Webhook de Telegram con el Secret Token de seguridad';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('services.telegram.bot_token');
        $secret = config('services.telegram.webhook_secret');
        $appUrl = config('app.url');
        
        if (!$token) {
            $this->error('❌ ERROR: Falta TELEGRAM_BOT_TOKEN en tu archivo .env');
            return 1;
        }

        if (!$secret) {
            $this->error('❌ ERROR: Falta TELEGRAM_WEBHOOK_SECRET en tu archivo .env');
            $this->warn('💡 Acción: Crea una clave aleatoria fuerte y ponla en tu .env para blindar el webhook.');
            return 1;
        }

        if (str_contains($appUrl, 'localhost')) {
            $this->warn('⚠️ ADVERTENCIA: Tu APP_URL parece ser localhost.');
            $this->info('Recuerda que Telegram necesita una URL pública (puedes usar ngrok o similar para pruebas locales).');
        }

        $webhookUrl = rtrim($appUrl, '/') . '/telegram/webhook';

        $this->newLine();
        $this->info("🛰️  Enviando solicitud a Telegram...");
        $this->line("📍 URL: <info>{$webhookUrl}</info>");
        $this->line("🔐 Secret Token: <comment>Configurado</comment>");
        $this->newLine();

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $secret,
                'drop_pending_updates' => true,
            ]);

            if ($response->successful() && $response->json('ok')) {
                $this->info('✅ ¡ÉXITO! El Webhook de Telegram ha sido configurado correctamente.');
                $this->line('📝 Respuesta: ' . $response->json('description'));
                return 0;
            }

            $this->error('❌ error al configurar el Webhook');
            $this->error('Respuesta de Telegram: ' . ($response->json('description') ?? 'Error desconocido'));
            
        } catch (\Exception $e) {
            $this->error('❌ Se produjo una excepción al conectar con Telegram:');
            $this->error($e->getMessage());
        }
        
        return 1;
    }
}
