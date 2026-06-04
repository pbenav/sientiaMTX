<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSyncController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!config('services.whatsapp.enabled', true)) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => 'El módulo de WhatsApp está globalmente desactivado.'], 403);
                }
                abort(403, 'El módulo de WhatsApp está globalmente desactivado.');
            }
            return $next($request);
        });
    }

    /**
     * Sincroniza el historial de WhatsApp del equipo tras reconexión o desconexión.
     */
    public function sync(Request $request)
    {
        try {
            $team = \App\Models\Team::findOrFail($request->get('team_id'));
            if (!$team->members->contains(auth()->id()) && !auth()->user()->is_admin) {
                abort(403);
            }

            $chatId = $team->whatsapp_chat_id;
            if (!$chatId) {
                return response()->json(['success' => false, 'error' => 'No hay número de WhatsApp vinculado a este equipo.'], 422);
            }

            $session = 'team_' . ($team->slug ?: $team->id);

            // 1. Sincronizar mensajes locales offline que NO se enviaron a WhatsApp (message_id es null o vacío)
            $pendingMessages = \App\Models\WhatsappMessage::where('team_id', $team->id)
                ->where('from_me', true)
                ->where(function($q) {
                    $q->whereNull('message_id')->orWhere('message_id', '');
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $sentCount = 0;
            foreach ($pendingMessages as $msg) {
                $caption = "💬 *[{$msg->author}]:*\n{$msg->text}";
                $params = [
                    'session' => $session,
                    'phone' => $chatId,
                    'message' => $caption,
                ];

                if ($msg->photo_path) {
                    $fullPath = storage_path('app/public/' . $msg->photo_path);
                    if (file_exists($fullPath)) {
                        $params['mediaBase64'] = base64_encode(file_get_contents($fullPath));
                        $params['mediaMimetype'] = mime_content_type($fullPath);
                        $params['mediaFilename'] = basename($fullPath);
                    }
                } elseif ($msg->voice_path) {
                    $fullPath = storage_path('app/public/' . $msg->voice_path);
                    if (file_exists($fullPath)) {
                        $params['mediaBase64'] = base64_encode(file_get_contents($fullPath));
                        $params['mediaMimetype'] = mime_content_type($fullPath);
                        $params['mediaFilename'] = basename($fullPath);
                    }
                }

                $params['webhook_url'] = route('whatsapp.webhook');

                try {
                    $responseSend = Http::post("http://localhost:3001/api/send", $params);
                    if ($responseSend->successful()) {
                        $dataSend = $responseSend->json();
                        if (isset($dataSend['message_id'])) {
                            $msg->update(['message_id' => $dataSend['message_id']]);
                            $sentCount++;

                            // Enviar espejo a Telegram si está activo
                            $creator = $team->creator;
                            $creatorSettings = $creator ? ($creator->notification_settings ?? $creator->defaultNotificationSettings()) : null;
                            $isSyncEnabled = $creator ? ($creatorSettings['sync_chats'] ?? false) : true;
                            if ($isSyncEnabled) {
                                $botToken = config('services.telegram.bot_token');
                                if ($botToken && $team->telegram_chat_id && !empty($msg->text)) {
                                    $cleanBody = strip_tags($msg->text);
                                    Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                        'chat_id' => $team->telegram_chat_id,
                                        'text' => "🟢 [WhatsApp] {$msg->author}:\n{$cleanBody}",
                                    ]);
                                    
                                    // Crear registro espejo de Telegram
                                    \App\Models\TelegramMessage::create([
                                        'team_id' => $team->id,
                                        'author_name' => "🟢 [WhatsApp] {$msg->author}",
                                        'text' => $cleanBody,
                                        'file_type' => 'text',
                                        'telegram_message_id' => 'sync_' . uniqid(),
                                        'is_from_web' => true,
                                        'file_size' => 0,
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $exSend) {
                    Log::error("Error sincronizando mensaje offline id {$msg->id}: " . $exSend->getMessage());
                }
            }

            // 2. Sincronizar mensajes recibidos en el teléfono físico hacia Sientia (comportamiento por defecto)
            $response = Http::timeout(60)->post('http://localhost:3001/api/sync', [
                'session' => $session,
                'phone' => $chatId,
                'limit' => 50
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $result['success'] = true;
                $result['offline_messages_sent'] = $sentCount;
                return response()->json($result);
            }

            return response()->json([
                'success' => false, 
                'offline_messages_sent' => $sentCount,
                'error' => 'El servicio de WhatsApp de este equipo no está conectado o listo para la descarga de mensajes nuevos.'
            ], 502);

        } catch (\Exception $e) {
            Log::error('Error en sincronización de WhatsApp: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
