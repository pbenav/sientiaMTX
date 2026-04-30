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
        // Security Audit Fix: Verify the secret token from Telegram
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            Log::warning('Unauthorized Telegram Webhook attempt from IP: ' . $request->ip());
            return response()->json(['status' => 'unauthorized'], 403);
        }

        $update = $request->all();
        
        // Log detallado para diagnóstico
        Log::info('Telegram Update Received:', [
            'update_id' => $update['update_id'] ?? 'unknown',
            'has_message' => isset($update['message']),
            'has_edited_message' => isset($update['edited_message']),
            'chat_id' => $update['message']['chat']['id'] ?? $update['edited_message']['chat']['id'] ?? 'N/A'
        ]);

        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (!$message || !isset($message['chat']['id'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = trim((string) $message['chat']['id']); // Limpiar espacios por seguridad
        $text = $message['text'] ?? $message['caption'] ?? '';
        $messageId = $message['message_id'] ?? null;
        $from = $message['from'] ?? [];
        $firstName = $from['first_name'] ?? 'Usuario';
        $lastName = $from['last_name'] ?? '';
        $authorName = trim($firstName . ' ' . $lastName);
        
        $replyTo = $message['reply_to_message'] ?? null;
        $replyToMessageId = $replyTo['message_id'] ?? null;
        $replyToText = $replyTo['text'] ?? $replyTo['caption'] ?? null;

        // Handle Media
        $photoPath = null;
        $voicePath = null;
        $voiceDuration = null;
        $stickerPath = null;
        $fileType = 'text';

        if (isset($message['photo'])) {
            $fileId = end($message['photo'])['file_id'];
            $photoPath = $this->downloadFile($fileId, 'photos');
            $fileType = 'photo';
        } elseif (isset($message['voice'])) {
            $fileId = $message['voice']['file_id'];
            $voiceDuration = $message['voice']['duration'];
            $voicePath = $this->downloadFile($fileId, 'voice');
            $fileType = 'voice';
        } elseif (isset($message['animation'])) {
            // GIFs sent via Telegram are actually MP4 animations
            $fileId = $message['animation']['file_id'];
            $photoPath = $this->downloadFile($fileId, 'animations');
            $fileType = 'animation';
        } elseif (isset($message['sticker'])) {
            $stickerData = $message['sticker'];
            $fileId = $stickerData['file_id'];
            $stickerPath = $this->downloadFile($fileId, 'stickers');
            // Detect sticker type for proper frontend rendering
            if (!empty($stickerData['is_animated'])) {
                $fileType = 'sticker_animated'; // .tgs (Lottie JSON)
            } elseif (!empty($stickerData['is_video'])) {
                $fileType = 'sticker_video'; // .webm
            } else {
                $fileType = 'sticker'; // .webp (static)
            }
        }

        // 1. Check if it's a private chat /start
        if ($chatId > 0 && str_starts_with($text, '/start')) {
            $this->sendMessage($chatId, "👋 *¡Hola! Bienvenido a SientiaMTX.*\n\n" .
                "Tu Chat ID personal es:\n`{$chatId}`\n\n" .
                "Si quieres vincular un *EQUIPO*, añade este bot a un grupo y escribe `/vincular` allí.");
            return response()->json(['status' => 'success']);
        }

        // 2. Check for /vincular command in group
        if (str_starts_with($text, '/vincular')) {
            $this->sendMessage($chatId, "🔗 *¡Oído cocina!*\n\nPara vincular este grupo a un equipo de SientiaMTX, copia este ID en la configuración de tu equipo:\n\n`{$chatId}`");
            return response()->json(['status' => 'success']);
        }

        // 3. Handle messages from groups that are already linked to a team
        $team = \App\Models\Team::where('telegram_chat_id', $chatId)->first();
        
        if (!$team) {
            Log::warning("Telegram Webhook: No se encontró equipo para el chat_id '{$chatId}'");
            return response()->json(['status' => 'success']); 
        }

        if ($messageId) {
            // Check if we already have this message (IMPORTANT: Scope to team_id to avoid collisions)
            $existing = \App\Models\TelegramMessage::where('team_id', $team->id)
                ->where('telegram_message_id', $messageId)
                ->first();
            
            // Si es un mensaje editado, actualizamos el texto si ya existe
            if ($existing && isset($update['edited_message'])) {
                $existing->update(['text' => $text . ' (editado)']);
                return response()->json(['status' => 'success']);
            }

            if (!$existing) {
                $fileSize = 0;
                
                // If there's media, check if we have space before downloading
                if ($photoPath || $voicePath || $stickerPath) {
                    $path = $photoPath ?: ($voicePath ?: $stickerPath);
                    if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        $fileSize = \Illuminate\Support\Facades\Storage::disk('public')->size($path);
                    }
                }

                // SECURITY/QUOTA: If team is over limit, we don't save more media
                if ($fileSize > 0 && !$team->hasAvailableQuota($fileSize)) {
                    // Delete the physical file we just downloaded temporarily
                    if ($photoPath) { \Illuminate\Support\Facades\Storage::disk('public')->delete($photoPath); $photoPath = null; }
                    if ($voicePath) { \Illuminate\Support\Facades\Storage::disk('public')->delete($voicePath); $voicePath = null; }
                    if ($stickerPath) { \Illuminate\Support\Facades\Storage::disk('public')->delete($stickerPath); $stickerPath = null; }
                    
                    $this->sendMessage($chatId, "⚠️ *¡ATENCIÓN!* El espacio de almacenamiento de vuestro equipo en Sientia MTX está *AGOTADO*.\n\nNo se ha podido guardar la imagen/audio.");
                    $fileSize = 0;
                    $fileType = 'text (storage full)';
                }

                \App\Models\TelegramMessage::create([
                    'team_id' => $team->id,
                    'author_name' => $authorName,
                    'text' => $text,
                    'photo_path' => $photoPath,
                    'voice_path' => $voicePath,
                    'voice_duration' => $voiceDuration,
                    'sticker_path' => $stickerPath,
                    'file_type' => $fileType,
                    'telegram_message_id' => $messageId,
                    'is_from_web' => false,
                    'file_size' => $fileSize,
                    'reply_to_message_id' => $replyToMessageId,
                    'reply_to_text' => $replyToText,
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Download file from Telegram.
     */
    protected function downloadFile($fileId, $subfolder): ?string
    {
        try {
            $token = config('services.telegram.bot_token');
            
            $fileResponse = Http::get("https://api.telegram.org/bot{$token}/getFile", ['file_id' => $fileId]);
            if (!$fileResponse->successful()) return null;
            
            $filePath = $fileResponse->json()['result']['file_path'] ?? null;
            if (!$filePath) return null;

            $fileContent = Http::get("https://api.telegram.org/file/bot{$token}/{$filePath}");
            
            if (!$fileContent->successful()) return null;

            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if (!$ext) {
                if ($subfolder === 'voice') $ext = 'ogg';
                if ($subfolder === 'stickers') $ext = 'webp';
            }

            // .tgs files are gzip-compressed Lottie JSON — decompress them so lottie-web can render them
            if ($ext === 'tgs') {
                $decompressed = @gzdecode($fileContent->body());
                if ($decompressed !== false) {
                    $localName = "telegram/{$subfolder}/" . uniqid() . '.json';
                    \Illuminate\Support\Facades\Storage::disk('public')->put($localName, $decompressed);
                    return $localName;
                }
            }

            $localName = "telegram/{$subfolder}/" . uniqid() . '.' . $ext;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($localName, $fileContent->body());
            
            return $localName;
        } catch (\Exception $e) {
            Log::error("Error downloading Telegram file ({$subfolder}): " . $e->getMessage());
            return null;
        }
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
