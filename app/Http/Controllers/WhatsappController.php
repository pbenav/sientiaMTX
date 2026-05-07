<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * Muestra la vista de configuración y el QR de WhatsApp.
     */
    public function index()
    {
        // Consultamos el estado al servicio Node.js (opcional para precargar datos)
        $status = ['ready' => false, 'qr' => null];
        try {
            $response = Http::timeout(2)->get('http://localhost:3001/api/status', [
                'webhook_url' => route('whatsapp.webhook')
            ]);
            if ($response->successful()) {
                $status = $response->json();
            }
        } catch (\Exception $e) {
            // El servicio de Node.js no está corriendo
        }

        return view('whatsapp.index', compact('status'));
    }

    /**
     * Recibe los mensajes entrantes desde el servicio Node.js (Webhook)
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        
        Log::info('WhatsApp Webhook recibido:', $payload);
        
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
                                Log::info("Sincronización Web: Reenviando mensaje enviado desde la web a Telegram");
                                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                    'chat_id' => $team->telegram_chat_id,
                                    'text' => "🟢 [WhatsApp] {$author}:\n{$cleanBody}",
                                ]);
                                // Crear registro espejo de Telegram para que aparezca en el widget de la web de inmediato
                                \App\Models\TelegramMessage::create([
                                    'team_id' => $team->id,
                                    'author_name' => "🟢 [WhatsApp] {$author}",
                                    'text' => $cleanBody,
                                    'file_type' => 'text',
                                    'telegram_message_id' => 'sync_' . uniqid(),
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

                if ($isSyncEnabled) {
                    $botToken = config('services.telegram.bot_token');
                    if ($botToken && $team->telegram_chat_id && !empty($body) && !str_contains($body, '🔵 [Telegram]')) {
                        $cleanBody = strip_tags($body);
                        Log::info("Sincronización: Reenviando mensaje de WhatsApp a Telegram para el equipo {$team->name}");
                        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $team->telegram_chat_id,
                            'text' => "🟢 [WhatsApp] {$author}:\n{$cleanBody}",
                        ]);

                        // Crear registro espejo de Telegram para que aparezca en el widget de la web de inmediato
                        \App\Models\TelegramMessage::create([
                            'team_id' => $team->id,
                            'author_name' => "🟢 [WhatsApp] {$author}",
                            'text' => $cleanBody,
                            'file_type' => $type,
                            'photo_path' => $photoPath,
                            'voice_path' => $voicePath,
                            'sticker_path' => $stickerPath,
                            'telegram_message_id' => 'sync_' . uniqid(),
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

    /**
     * Método auxiliar para enviar mensajes a través del servicio Node.js
     */
    public function sendMessage($phone, $message, $session = null)
    {
        $user = auth()->user();
        $notifSettings = $user ? ($user->notification_settings ?? $user->defaultNotificationSettings()) : null;
        if ($user && !($notifSettings['whatsapp'] ?? false)) {
            return ['success' => false, 'error' => 'Módulo de WhatsApp desactivado.'];
        }

        try {
            $payload = [
                'phone' => $phone,
                'message' => $message,
            ];
            
            if ($session) {
                $payload['session'] = $session;
            }

            $response = Http::post('http://localhost:3001/api/send', $payload);

            // Fallback resiliente: Si la sesión específica falla o no está lista, intentamos con default
            if (!$response->successful() && $session && $session !== 'default') {
                Log::info("Envío fallido con sesión {$session}. Reintentando con sesión default como fallback...");
                $payload['session'] = 'default';
                $response = Http::post('http://localhost:3001/api/send', $payload);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de WhatsApp: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reinicia el cliente de WhatsApp en Node.js para generar un nuevo QR
     */
    public function restart(Request $request)
    {
        $user = auth()->user();
        $notifSettings = $user->notification_settings ?? $user->defaultNotificationSettings();
        if (!($notifSettings['whatsapp'] ?? false)) {
            return response()->json(['success' => false, 'error' => 'Módulo de WhatsApp desactivado.'], 403);
        }

        try {
            $response = Http::timeout(10)->post('http://localhost:3001/api/restart');
            return response()->json(['success' => true, 'message' => 'Reiniciando cliente']);
        } catch (\Exception $e) {
            Log::error('Error reiniciando WhatsApp: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy de estado global de WhatsApp Web Bridge
     */
    public function status(Request $request)
    {
        try {
            $response = Http::timeout(3)->get('http://localhost:3001/api/status');
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['ready' => false, 'qr' => ''], 502);
        } catch (\Exception $e) {
            return response()->json(['ready' => false, 'qr' => '', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Proxy de estado de WhatsApp Personal
     */
    public function personalStatus(Request $request)
    {
        try {
            $session = 'user_' . auth()->id();
            $init = $request->get('init') === 'true' ? '&init=true' : '';
            $response = Http::timeout(3)->get('http://localhost:3001/api/status?session=' . $session . $init);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['ready' => false, 'qr' => ''], 502);
        } catch (\Exception $e) {
            return response()->json(['ready' => false, 'qr' => '', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Desvincula o reinicia la sesión de WhatsApp Personal
     */
    public function personalRestart(Request $request)
    {
        try {
            $session = 'user_' . auth()->id();
            $response = Http::timeout(10)->post('http://localhost:3001/api/restart', [
                'session' => $session
            ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error desvinculando WhatsApp Personal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy de estado de WhatsApp de Equipo
     */
    public function teamStatus(Request $request)
    {
        try {
            $team = \App\Models\Team::findOrFail($request->get('team_id'));
            if (!$team->members->contains(auth()->id()) && !auth()->user()->is_admin) {
                abort(403);
            }
            $session = 'team_' . $team->id;
            $init = $request->get('init') === 'true' ? '&init=true' : '';
            $response = Http::timeout(3)->get('http://localhost:3001/api/status?session=' . $session . $init);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['ready' => false, 'qr' => ''], 502);
        } catch (\Exception $e) {
            return response()->json(['ready' => false, 'qr' => '', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Desvincula o reinicia la sesión de WhatsApp de Equipo
     */
    public function teamRestart(Request $request)
    {
        try {
            $team = \App\Models\Team::findOrFail($request->get('team_id'));
            if ($team->user_id !== auth()->id() && !auth()->user()->is_admin) {
                abort(403);
            }
            $session = 'team_' . $team->id;
            $response = Http::timeout(10)->post('http://localhost:3001/api/restart', [
                'session' => $session
            ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error desvinculando WhatsApp de Equipo: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
