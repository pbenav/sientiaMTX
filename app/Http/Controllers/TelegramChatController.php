<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramMessage;
use App\Models\Team;

class TelegramChatController extends Controller
{
    /**
     * Enviar un mensaje desde la web al grupo de Telegram del equipo.
     */
     public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120', // Max 5MB
            'voice' => 'nullable|mimes:ogg,webm,mp3,wav|max:5120', // Max 5MB
            'team_id' => 'required|exists:teams,id',
            'reply_to_id' => 'nullable|exists:telegram_messages,id',
        ]);

        $user = auth()->user();
        $team = Team::findOrFail($request->team_id);
        $text = $request->input('message') ?? '';
        $photo = $request->file('photo');
        $voice = $request->file('voice');
        
        $chatId = $team->telegram_chat_id;

        if (!$chatId) {
            return response()->json([
                'reply' => '⚠️ Este equipo no tiene un grupo de Telegram vinculado.'
            ]);
        }

        $token = config('services.telegram.bot_token');

        try {
            $photoPath = null;
            $voicePath = null;
            $voiceDuration = null;
            $fileType = 'text';

            if ($photo) {
                $photoPath = $photo->store('telegram/photos', 'public');
                $fileType = 'photo';
            } elseif ($voice) {
                $voicePath = $voice->store('telegram/voice', 'public');
                $fileType = 'voice';
                // Note: duration could be calculated here if needed, but we'll leave it as null for web-sent voices for now
            } elseif (!$text) {
                return response()->json(['error' => 'Mensaje vacío'], 422);
            }

            // Guardamos el mensaje en nuestra DB local
            $localMsg = TelegramMessage::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'author_name' => $user->name,
                'text' => $text,
                'photo_path' => $photoPath,
                'voice_path' => $voicePath,
                'file_type' => $fileType,
                'is_from_web' => true,
                'reply_to_message_id' => $request->reply_to_id ? TelegramMessage::find($request->reply_to_id)?->telegram_message_id : null,
                'reply_to_text' => $request->reply_to_id ? TelegramMessage::find($request->reply_to_id)?->text : null,
            ]);

            // Formateamos el pie del mensaje usando HTML (más robusto que Markdown para tildes y caracteres especiales)
            // Formateamos el pie del mensaje usando Markdown (original)
            $caption = "💬 *[{$user->name}]:*\n{$text}";
            
            $params = [
                'chat_id' => $chatId,
                'parse_mode' => 'Markdown',
            ];

            if ($localMsg->reply_to_message_id) {
                $params['reply_to_message_id'] = $localMsg->reply_to_message_id;
            }

            if ($photo) {
                $params['caption'] = $caption;
                $response = Http::attach(
                    'photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName()
                )->post("https://api.telegram.org/bot{$token}/sendPhoto", $params);
            } elseif ($voice) {
                $params['caption'] = $caption;
                $response = Http::attach(
                    'voice', file_get_contents($voice->getRealPath()), $voice->getClientOriginalName()
                )->post("https://api.telegram.org/bot{$token}/sendVoice", $params);
            } else {
                $params['text'] = $caption;
                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $params);
            }

            if ($response->successful()) {
                $data = $response->json();
                $localMsg->update(['telegram_message_id' => $data['result']['message_id']]);
                
                Log::info("Mensaje enviado a Telegram, ID: {$data['result']['message_id']}");

                return response()->json([
                    'success' => true,
                    'message' => [
                        'id' => $localMsg->id,
                        'text' => $localMsg->text,
                        'author' => $localMsg->author_name,
                        'from_me' => true,
                        'time' => $localMsg->created_at->format('H:i'),
                        'photo' => $localMsg->photo_url,
                        'voice' => $localMsg->voice_url,
                        'sticker' => $localMsg->sticker_url,
                        'file_type' => $localMsg->file_type,
                    ]
                ]);
            }

            Log::error("Error de Telegram: " . $response->body());
            return response()->json([
                'reply' => '😕 No he podido enviar el mensaje a Telegram.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error en TelegramChatController@sendMessage: " . $e->getMessage());
            return response()->json([
                'reply' => '💥 Error técnico al enviar el mensaje.'
            ]);
        }
    }

    /**
     * Obtener el historial de mensajería de un equipo (paginado).
     */
    public function getMessages(Request $request)
    {
        $teamId = $request->query('team_id');
        $beforeId = $request->query('before_id');
        
        if (!$teamId) {
            return response()->json(['messages' => []]);
        }

        $query = TelegramMessage::where('team_id', $teamId)
            ->where('is_deleted_on_telegram', false)
            ->orderBy('created_at', 'desc');

        // Optional: Pagination to load older messages
        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->take(25)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'text' => $msg->text,
                    'author' => $msg->author_name,
                    'from_me' => $msg->user_id === auth()->id() && $msg->is_from_web,
                    'time' => $msg->created_at->format('H:i'),
                    'photo' => $msg->photo_url,
                    'voice' => $msg->voice_url,
                    'sticker' => $msg->sticker_url,
                    'file_type' => $msg->file_type,
                    'reply_to_text' => $msg->reply_to_text,
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Editar un mensaje tanto localmente como en Telegram.
     */
    public function update(Request $request, TelegramMessage $message)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $team = $message->team;

        // Solo el autor puede editar
        if ($message->user_id !== $user->id) {
            Log::warning("Intento de edición no autorizado: Usuario {$user->id} intentó editar mensaje {$message->id} (Autor: {$message->user_id})");
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $text = $request->input('message');
        $token = config('services.telegram.bot_token');
        $chatId = $team->telegram_chat_id;

        try {
            $message->update(['text' => $text]);

            if ($message->telegram_message_id && $chatId) {
                $formattedText = "💬 *[{$user->name}]:* (editado)\n{$text}";
                
                $method = $message->photo_path ? 'editMessageCaption' : 'editMessageText';
                $params = [
                    'chat_id' => $chatId,
                    'message_id' => $message->telegram_message_id,
                    'parse_mode' => 'Markdown',
                ];

                if ($message->photo_path) {
                    $params['caption'] = $formattedText;
                } else {
                    $params['text'] = $formattedText;
                }
                
                Http::post("https://api.telegram.org/bot{$token}/{$method}", $params);
            }

            return response()->json([
                'success' => true,
                'message' => $text
            ]);

        } catch (\Exception $e) {
            Log::error("Error en TelegramChatController@update: " . $e->getMessage());
            return response()->json(['error' => 'Error al editar el mensaje'], 500);
        }
    }

    /**
     * Eliminar un mensaje tanto localmente como en Telegram.
     */
    public function destroy(TelegramMessage $message)
    {
        $user = auth()->user();
        $team = $message->team;
        $isManager = $team->isManager($user);
        
        // Manual authorization check: Only author or a team manager can delete
        if ($message->user_id !== $user->id && !$isManager) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $token = config('services.telegram.bot_token');
        $chatId = $team->telegram_chat_id;

        if ($message->telegram_message_id && $chatId) {
            try {
                $response = Http::post("https://api.telegram.org/bot{$token}/deleteMessage", [
                    'chat_id' => $chatId,
                    'message_id' => $message->telegram_message_id,
                ]);
                
                if (!$response->successful()) {
                    Log::warning("Telegram deleteMessage failed: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Error deleting message from Telegram: " . $e->getMessage());
            }
        }

        // We mark as deleted locally so it doesn't show up in history
        $message->update(['is_deleted_on_telegram' => true]);
        
        return response()->json(['success' => true]);
    }
}
