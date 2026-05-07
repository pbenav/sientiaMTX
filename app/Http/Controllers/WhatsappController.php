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
                $botToken = config('services.telegram.bot_token');
                if ($botToken && $team->telegram_chat_id && !empty($body) && !str_contains($body, '🔵 *[Telegram]*')) {
                    $cleanBody = strip_tags($body);
                    \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $team->telegram_chat_id,
                        'text' => "🟢 *[WhatsApp] {$author}:*\n{$cleanBody}",
                        'parse_mode' => 'Markdown',
                    ]);
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
    public function sendMessage($phone, $message)
    {
        $user = auth()->user();
        $notifSettings = $user ? ($user->notification_settings ?? $user->defaultNotificationSettings()) : null;
        if ($user && !($notifSettings['whatsapp'] ?? false)) {
            return ['success' => false, 'error' => 'Módulo de WhatsApp desactivado.'];
        }

        try {
            $response = Http::post('http://localhost:3001/api/send', [
                'phone' => $phone,
                'message' => $message,
            ]);

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
}
