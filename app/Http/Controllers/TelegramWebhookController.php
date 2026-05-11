<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


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
        
        // Determinar si es un mensaje nuevo o editado (soporta mensajes de grupo y posts de canal/hilo)
        $isEdit = isset($update['edited_message']) || isset($update['edited_channel_post']);
        $message = $update['message'] ?? $update['edited_message'] ?? $update['edited_channel_post'] ?? null;

        if (!$message || !isset($message['chat']['id'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = trim((string) $message['chat']['id']);
        $text = $message['text'] ?? $message['caption'] ?? '';
        $messageId = $message['message_id'] ?? null;
        $from = $message['from'] ?? $message['sender_chat'] ?? [];
        $firstName = $from['first_name'] ?? $from['title'] ?? 'Usuario';
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

        // Deactivate backend processing if the team creator has disabled the Telegram module
        $creator = $team->creator;
        $creatorSettings = $creator ? ($creator->notification_settings ?? $creator->defaultNotificationSettings()) : null;
        if ($creatorSettings && !($creatorSettings['telegram'] ?? false)) {
            Log::info("Telegram Webhook ignorado: El creador del equipo {$team->name} tiene desactivado el módulo de Telegram.");
            return response()->json(['status' => 'success']);
        }

        if ($messageId) {
            // Check if we already have this message (IMPORTANT: Scope to team_id to avoid collisions)
            $existing = \App\Models\TelegramMessage::where('team_id', $team->id)
                ->where('telegram_message_id', $messageId)
                ->first();
            
            if ($existing) {
                if ($isEdit) {
                    $existing->update(['text' => $text]);
                }
                return response()->json(['status' => 'success']);
            }

            // Deduplicación inteligente: si un mensaje fue creado mediante sincronización web/espejo
            // tendrá un ID temporal del tipo 'sync_...' o nulo. Lo asociamos a este ID real de Telegram.
            $syncedMessage = \App\Models\TelegramMessage::where('team_id', $team->id)
                ->where(function ($q) {
                    $q->whereNull('telegram_message_id')
                      ->orWhere('telegram_message_id', 'like', 'sync_%');
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->get()
                ->first(function ($msg) use ($text) {
                    $cleanTextMsg = trim(strip_tags($msg->text));
                    $cleanIncomingText = trim(strip_tags($text));
                    return $cleanTextMsg === $cleanIncomingText 
                        || str_contains($cleanIncomingText, $cleanTextMsg) 
                        || str_contains($cleanTextMsg, $cleanIncomingText);
                });

            if ($syncedMessage) {
                Log::info("Telegram Webhook Deduplicación: Asociando mensaje sync '{$syncedMessage->telegram_message_id}' al ID real '{$messageId}'");
                $syncedMessage->update([
                    'telegram_message_id' => $messageId,
                    'is_from_web' => false
                ]);
                return response()->json(['status' => 'success']);
            }

            if (!$existing) {
                try {
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
                        Log::warning("Telegram Webhook: Cuota agotada para el equipo {$team->name}");
                        if ($photoPath) { \Illuminate\Support\Facades\Storage::disk('public')->delete($photoPath); $photoPath = null; }
                        if ($voicePath) { \Illuminate\Support\Facades\Storage::disk('public')->delete($voicePath); $voicePath = null; }
                        if ($stickerPath) { \Illuminate\Support\Facades\Storage::disk('public')->delete($stickerPath); $stickerPath = null; }
                        
                        $this->sendMessage($chatId, "⚠️ *¡ATENCIÓN!* El espacio de almacenamiento de vuestro equipo en Sientia MTX está *AGOTADO*.");
                        $fileSize = 0;
                        $fileType = 'text (storage full)';
                    }

                    $newMsg = \App\Models\TelegramMessage::create([
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

                    // Reenvío automático Inter-Bridge a WhatsApp si está configurado en el equipo
                    $creator = $team->creator;
                    $creatorSettings = $creator ? ($creator->notification_settings ?? $creator->defaultNotificationSettings()) : null;
                    $isSyncEnabled = $creator ? ($creatorSettings['sync_chats'] ?? false) : true;

                    $messageDate = $message['date'] ?? null;
                    $isTooOld = $messageDate && (time() - $messageDate) > 300;

                    if ($isSyncEnabled && !$isTooOld) {
                        if ($team->whatsapp_chat_id && (!empty($text) || $photoPath || $voicePath || $stickerPath) && !str_contains($text, '🟢 [WhatsApp]')) {
                            Log::info("Sincronización: Reenviando mensaje de Telegram a WhatsApp para el equipo {$team->name}");
                            
                            // 1. Determinar si el emisor de Telegram es el propio creador de la sala (para omitir prefijo redundante)
                            $creatorTelegramId = (string) ($creator->telegram_chat_id ?? '');
                            $incomingTelegramId = (string) ($from['id'] ?? '');
                            $isOwner = !empty($creatorTelegramId) && ($creatorTelegramId === $incomingTelegramId);

                            $cleanText = trim(strip_tags($text));
                            // Si hay imagen pero no texto, enviamos vacío, de lo contrario formateamos
                            $whatsappBody = $isOwner ? $cleanText : "🔵 [Telegram] {$authorName}:" . (!empty($cleanText) ? "\n{$cleanText}" : "");

                            $payload = [
                                'phone' => $team->whatsapp_chat_id,
                                'message' => $whatsappBody,
                                'webhook_url' => route('whatsapp.webhook'),
                                'session' => 'team_' . ($team->slug ?: $team->id)
                            ];

                            // 2. Enriquecer payload con Multimedia Base64 si existe
                            $activeMedia = $photoPath ?: ($voicePath ?: $stickerPath);
                            if ($activeMedia) {
                                try {
                                    $disk = \Illuminate\Support\Facades\Storage::disk('public');
                                    if ($disk->exists($activeMedia)) {
                                        $payload['mediaBase64'] = base64_encode($disk->get($activeMedia));
                                        $payload['mediaMimetype'] = $disk->mimeType($activeMedia) ?: 'application/octet-stream';
                                        $payload['mediaFilename'] = basename($activeMedia);
                                    }
                                } catch (\Exception $eMedia) {
                                    Log::warning("TelegramWebhook: Error leyendo multimedia para WhatsApp sync: " . $eMedia->getMessage());
                                }
                            }

                            // 3. Realizar la petición al bridge
                            $syncResponse = \Illuminate\Support\Facades\Http::timeout(10)->post("http://localhost:3001/api/send", $payload);
                            
                            if (!$syncResponse->successful()) {
                                Log::warning("Fallo envío WhatsApp primario. Reintentando con fallback default...");
                                $payload['session'] = 'default';
                                \Illuminate\Support\Facades\Http::timeout(10)->post("http://localhost:3001/api/send", $payload);
                            }
                        }
                    } else {
                        Log::info("Sincronización desactivada por preferencia de perfil para el equipo {$team->name}");
                    }

                } catch (\Exception $e) {
                    Log::error("Telegram Webhook: ERROR CRÍTICO al guardar mensaje: " . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
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
