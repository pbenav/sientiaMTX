<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Recibe los mensajes entrantes desde el servicio Node.js (Webhook)
     */
    public function webhook(Request $request)
    {
        if (!config('services.whatsapp.enabled', true)) {
            return response()->json(['status' => 'disabled']);
        }

        $secret = config('services.whatsapp.webhook_secret');
        if ($secret && $request->header('X-Signature')) {
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);
            if ($request->header('X-Signature') !== $expectedSignature) {
                Log::warning('Firma X-Signature inválida en WhatsApp Webhook desde IP: ' . $request->ip());
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();
        
        $from = $payload['from'] ?? null;
        $to = $payload['to'] ?? null;
        $body = $payload['body'] ?? '';
        $type = $payload['type'] ?? 'text';
        $messageId = $payload['id'] ?? null;
        $fromMe = filter_var($payload['fromMe'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $author = $payload['author'] ?? 'Usuario';
        
        if (!$from) {
            return response()->json(['status' => 'ignored']);
        }

        // Determinar el identificador de chat adecuado para buscar el equipo
        $chatId = $from;
        $cleanChatId = preg_replace('/[^0-9]/', '', $chatId);
        
        $teamQuery = \App\Models\Team::where('whatsapp_chat_id', $chatId)
            ->orWhere('whatsapp_chat_id', $cleanChatId);
            
        if ($fromMe && $to) {
            $toClean = preg_replace('/[^0-9]/', '', $to);
            $teamQuery->orWhere('whatsapp_chat_id', $to)
                 ->orWhere('whatsapp_chat_id', $toClean);
        }
        
        $team = $teamQuery->first();

        if (!$team) {
            Log::warning("WhatsApp Webhook: No se encontró equipo para el chat_id '{$chatId}'");
            return response()->json(['status' => 'ignored']);
        }

        // Desactivar procesamiento si el creador desactivó WhatsApp
        $creator = $team->creator;
        $creatorSettings = $creator ? ($creator->notification_settings ?? $creator->defaultNotificationSettings()) : null;
        if ($creatorSettings && !($creatorSettings['whatsapp'] ?? false)) {
            Log::info("WhatsApp Webhook ignorado: El creador del equipo {$team->name} tiene desactivado el módulo de WhatsApp.");
            return response()->json(['status' => 'success']);
        }

        if ($messageId) {
            // Comprobamos si ya existe el mensaje en este equipo
            $existing = \App\Models\WhatsappMessage::where('team_id', $team->id)
                ->where('message_id', $messageId)
                ->first();
                
            if ($existing) {
                return response()->json(['status' => 'success']);
            }

            // Deduplicación inteligente para mensajes enviados desde la propia web (fromMe)
            if ($fromMe) {
                $pending = \App\Models\WhatsappMessage::where('team_id', $team->id)
                    ->where('from_me', true)
                    ->whereNull('message_id')
                    ->where('created_at', '>=', now()->subSeconds(30))
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                foreach ($pending as $pMsg) {
                    if ($pMsg->text === $body || ($pMsg->text && str_contains($body, $pMsg->text))) {
                        $pMsg->update(['message_id' => $messageId]);

                        // Reenvío inmediato a Telegram para mensajes originados en la propia web de WhatsApp
                        $creator = $team->creator;
                        $creatorSettings = $creator ? ($creator->notification_settings ?? $creator->defaultNotificationSettings()) : null;
                        $isSyncEnabled = $creator ? ($creatorSettings['sync_chats'] ?? false) : true;
                        if ($isSyncEnabled) {
                            $botToken = config('services.telegram.bot_token');
                            if ($botToken && $team->telegram_chat_id && !empty($body)) {
                                $cleanBody = strip_tags($body);
                                $tgResponse = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                    'chat_id' => $team->telegram_chat_id,
                                    'text' => "🟢 [WhatsApp] {$author}:\n{$cleanBody}",
                                ]);
                                
                                $realTgId = $tgResponse->successful() ? $tgResponse->json('result.message_id') : ('sync_' . uniqid());

                                // Crear registro espejo de Telegram para que aparezca en el widget de la web de inmediato
                                \App\Models\TelegramMessage::create([
                                    'team_id' => $team->id,
                                    'author_name' => "🟢 [WhatsApp] {$author}",
                                    'text' => $cleanBody,
                                    'file_type' => 'text',
                                    'telegram_message_id' => $realTgId,
                                    'is_from_web' => true,
                                    'file_size' => 0,
                                ]);
                            }
                        }

                        return response()->json(['status' => 'success']);
                    }
                }
            }
            
            try {
                $photoPath = null;
                $voicePath = null;
                $stickerPath = null;
                $fileSize = 0;

                // Guardar multimedia si viene en el payload
                if (!empty($payload['mediaData']) && !empty($payload['mediaMimetype'])) {
                    $fileData = base64_decode($payload['mediaData']);
                    $mime = $payload['mediaMimetype'];
                    $ext = explode('/', $mime)[1] ?? 'bin';
                    $fileSize = strlen($fileData);

                    // Verificar cuota de disco
                    if ($fileSize > 0 && !$team->hasAvailableQuota($fileSize)) {
                        Log::warning("WhatsApp Webhook: Cuota agotada para el equipo {$team->name}");
                        $fileSize = 0;
                    } else {
                        if ($type === 'image' || $type === 'photo' || $ext === 'jpeg' || $ext === 'jpg' || $ext === 'png') {
                            $photoPath = 'whatsapp/photos/' . uniqid() . '.' . $ext;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($photoPath, $fileData);
                            $type = 'photo';
                        } elseif ($type === 'audio' || $type === 'voice' || str_starts_with($mime, 'audio/')) {
                            $voicePath = 'whatsapp/voice/' . uniqid() . '.' . ($ext === 'ogg' ? 'ogg' : 'webm');
                            \Illuminate\Support\Facades\Storage::disk('public')->put($voicePath, $fileData);
                            $type = 'voice';
                        } elseif ($type === 'sticker' || $mime === 'image/webp') {
                            $stickerPath = 'whatsapp/stickers/' . uniqid() . '.webp';
                            \Illuminate\Support\Facades\Storage::disk('public')->put($stickerPath, $fileData);
                            $type = 'sticker';
                        }
                    }
                }

                \App\Models\WhatsappMessage::create([
                    'team_id' => $team->id,
                    'message_id' => $messageId,
                    'from_me' => $fromMe,
                    'author' => $author,
                    'text' => $body,
                    'file_type' => $type,
                    'photo_path' => $photoPath,
                    'voice_path' => $voicePath,
                    'sticker_path' => $stickerPath,
                    'file_size' => $fileSize,
                ]);

                // Reenvío automático Inter-Bridge a Telegram si está configurado en el equipo
                $creator = $team->creator;
                $creatorSettings = $creator ? ($creator->notification_settings ?? $creator->defaultNotificationSettings()) : null;
                $isSyncEnabled = $creator ? ($creatorSettings['sync_chats'] ?? false) : true;

                $timestamp = $payload['timestamp'] ?? null;
                $isTooOld = $timestamp && (time() - $timestamp) > 300;

                if ($isSyncEnabled && !$isTooOld) {
                    $botToken = config('services.telegram.bot_token');
                    $hasBody = !empty($body) && !str_contains($body, '🔵 [Telegram]');
                    $hasMedia = $photoPath || $voicePath || $stickerPath;

                    if ($botToken && $team->telegram_chat_id && ($hasBody || $hasMedia)) {
                        $cleanBody = strip_tags($body);
                        $caption = "🟢 [WhatsApp] {$author}:" . (!empty($cleanBody) ? "\n{$cleanBody}" : "");
                        Log::info("Sincronización WA->TG: Reenviando evento para el equipo {$team->name}");
                        
                        $tgRealId = 'sync_' . uniqid();
                        try {
                            $disk = \Illuminate\Support\Facades\Storage::disk('public');
                            $tgResponse = null;

                            if ($photoPath && $disk->exists($photoPath)) {
                                $tgResponse = \Illuminate\Support\Facades\Http::attach(
                                    'photo', $disk->get($photoPath), basename($photoPath)
                                )->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                                    'chat_id' => $team->telegram_chat_id,
                                    'caption' => $caption
                                ]);
                            } elseif ($voicePath && $disk->exists($voicePath)) {
                                $tgResponse = \Illuminate\Support\Facades\Http::attach(
                                    'voice', $disk->get($voicePath), basename($voicePath)
                                )->post("https://api.telegram.org/bot{$botToken}/sendVoice", [
                                    'chat_id' => $team->telegram_chat_id,
                                    'caption' => $caption
                                ]);
                            } elseif ($stickerPath && $disk->exists($stickerPath)) {
                                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                    'chat_id' => $team->telegram_chat_id,
                                    'text' => $caption
                                ]);
                                $tgResponse = \Illuminate\Support\Facades\Http::attach(
                                    'sticker', $disk->get($stickerPath), basename($stickerPath)
                                )->post("https://api.telegram.org/bot{$botToken}/sendSticker", [
                                    'chat_id' => $team->telegram_chat_id
                                ]);
                            } elseif ($hasBody) {
                                $tgResponse = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                    'chat_id' => $team->telegram_chat_id,
                                    'text' => $caption,
                                ]);
                            }

                            if ($tgResponse && $tgResponse->successful()) {
                                $tgRealId = $tgResponse->json('result.message_id');
                            }
                        } catch (\Exception $eRelay) {
                            Log::warning("Error en Relevo Espejo WA->TG: " . $eRelay->getMessage());
                        }

                        // Crear registro espejo de Telegram para que aparezca en el widget de la web de inmediato
                        \App\Models\TelegramMessage::create([
                            'team_id' => $team->id,
                            'author_name' => "🟢 [WhatsApp] {$author}",
                            'text' => $cleanBody,
                            'file_type' => $type,
                            'photo_path' => $photoPath,
                            'voice_path' => $voicePath,
                            'sticker_path' => $stickerPath,
                            'telegram_message_id' => $tgRealId,
                            'is_from_web' => true,
                            'file_size' => $fileSize,
                        ]);
                    }
                } else {
                    Log::info("Sincronización desactivada por preferencia de perfil para el equipo {$team->name}");
                }

            } catch (\Exception $e) {
                Log::error("WhatsApp Webhook: ERROR al guardar mensaje: " . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success']);
    }
}
