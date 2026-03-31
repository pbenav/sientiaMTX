<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming Telegram bot messages.
     */
    public function handle(Request $request)
    {
        $update = $request->all();

        // Basic validation
        if (!isset($update['message']['chat']['id'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';

        // If the user sends /start, we return their chat ID
        if (str_starts_with($text, '/start')) {
            $this->sendMessage($chatId, "👋 *¡Hola! Bienvenido a SientiaMTX.*\n\n" .
                "Tu Chat ID para recibir notificaciones es:\n`{$chatId}`\n\n" .
                "Cópialo y pégalo en tu *Perfil > Notificaciones* dentro de la aplicación.");
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Send a message back to the user via Telegram API.
     */
    protected function sendMessage($chatId, $text)
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            Log::error('Telegram Bot Token missing for webhook response.');
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error("Error sending Telegram webhook response: " . $e->getMessage());
        }
    }
}
