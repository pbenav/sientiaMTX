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
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = auth()->user();
        $team = Team::findOrFail($request->team_id);
        $text = $request->input('message') ?? '';
        $photo = $request->file('photo');
        
        $chatId = $team->telegram_chat_id;

        if (!$chatId) {
            return response()->json([
                'reply' => '⚠️ Este equipo no tiene un grupo de Telegram vinculado.'
            ]);
        }

        $token = config('services.telegram.bot_token');

        try {
            $photoPath = null;
            if ($photo) {
                // Si mandamos foto, el texto es opcional (caption)
                $photoPath = $photo->store('telegram_photos', 'public');
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
                'is_from_web' => true,
            ]);

            // Formateamos el pie del mensaje
            $caption = "💬 *[{$user->name}]:*\n{$text}";
            
            if ($photo) {
                $response = Http::attach(
                    'photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName()
                )->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown',
                ]);
            } else {
                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $caption,
                    'parse_mode' => 'Markdown',
                ]);
            }

            if ($response->successful()) {
                $data = $response->json();
                $localMsg->update(['telegram_message_id' => $data['result']['message_id']]);
                
                return response()->json([
                    'success' => true,
                    'message' => [
                        'id' => $localMsg->id,
                        'text' => $localMsg->text,
                        'author' => $localMsg->author_name,
                        'from_me' => true,
                        'time' => $localMsg->created_at->format('H:i'),
                        'photo' => $localMsg->photo_url,
                    ]
                ]);
            }

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
            abort(403);
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
