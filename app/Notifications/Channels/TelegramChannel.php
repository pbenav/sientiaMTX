<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $chatId = $notifiable->telegram_chat_id;
        if (!$chatId) {
            return;
        }

        $data = $notification->toTelegram($notifiable);
        $token = config('services.telegram.bot_token');

        if (!$token) {
            Log::warning('Telegram bot token not configured.');
            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $data['text'] ?? '',
            'parse_mode' => 'Markdown',
        ]);

        if ($response->failed()) {
            Log::error('Telegram notification failed: ' . $response->body());
        }
    }
}
