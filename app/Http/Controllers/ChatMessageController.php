<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestionar las operaciones de chat (mensajes individuales y grupales).
 *
 * Maneja la carga de historial de mensajes, envío, borrado masivo y eliminación individual.
 */
class ChatMessageController extends Controller
{
    /**
     * Obtiene el historial de mensajes entre dos usuarios o dentro de un grupo.
     *
     * Marca los mensajes como leídos y las notificaciones de Laravel como leídas.
     * Para grupos, verifica la membresía del usuario antes de devolver los mensajes.
     *
     * @param  string  $identifier  ID del usuario receptor o 'group_{id}' para grupos
     * @return \Illuminate\Http\JsonResponse Respuesta con member info y array de mensajes formateados
     */
    public function index($identifier): JsonResponse
    {
        $userId = auth()->id();
        Log::info("ChatMessageController@index starting", ['user' => $userId, 'identifier' => $identifier]);

        try {
            $isGroup = str_starts_with($identifier, 'group_');
            $groupId = $isGroup ? (int) str_replace('group_', '', $identifier) : null;
            $receiverId = !$isGroup ? (int) $identifier : null;
            
            if ($isGroup) {
                $group = \App\Models\ChatGroup::with('users')->find($groupId);
                if (!$group) return response()->json(['error' => 'Group not found'], 404);

                if (!$group->users()->where('user_id', $userId)->exists()) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
                
                // Mark as read for this user in pivot table
                $group->users()->updateExistingPivot($userId, ['last_read_at' => now()]);

                $messages = ChatMessage::where('chat_group_id', $groupId)
                    ->with(['sender', 'parent.sender'])
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
                $namesList = $group->users->map(function($u) use ($userId) {
                    return $u->id === $userId ? 'Tú' : explode(' ', trim($u->name))[0];
                });
                $me = $namesList->filter(fn($n) => $n === 'Tú');
                $others = $namesList->filter(fn($n) => $n !== 'Tú');
                $statusString = $me->merge($others)->implode(', ') . ' (' . $group->users->count() . ')';

                $memberInfo = [
                    'id' => 'group_' . $group->id,
                    'name' => $group->name,
                    'photo' => $group->avatar,
                    'team' => 'Chat Grupal',
                    'status' => $statusString,
                    'is_group' => true
                ];
            } else {
                // Mark as read
                ChatMessage::where('sender_id', $receiverId)
                    ->where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

                // TAMBIÉN marcar como leídas las NOTIFICACIONES de Laravel para este remitente
                auth()->user()->unreadNotifications()
                    ->where('type', 'App\Notifications\NewChatMessageNotification')
                    ->get()
                    ->filter(function ($n) use ($receiverId) {
                        return isset($n->data['sender_id']) && (int)$n->data['sender_id'] === (int)$receiverId;
                    })
                    ->each->markAsRead();

                $messages = ChatMessage::where(function ($query) use ($userId, $receiverId) {
                        $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
                    })
                    ->orWhere(function ($query) use ($userId, $receiverId) {
                        $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
                    })
                    ->with(['sender', 'parent.sender'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                $otherUser = User::find($receiverId);
                if (!$otherUser) return response()->json(['error' => 'User not found'], 404);

                $commonTeam = $otherUser->teams()->whereHas('members', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->first();

                $memberInfo = [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'photo' => $otherUser->profile_photo_url,
                    'team' => $commonTeam ? $commonTeam->name : null,
                    'status' => $otherUser->getStatusInfo()['label'],
                    'is_group' => false
                ];
            }

            return response()->json([
                'member' => $memberInfo,
                'messages' => $messages->map(function ($msg) use ($userId) {
                    return [
                        'id' => $msg->id,
                        'sender' => $msg->sender_id === $userId ? 'me' : 'them',
                        'sender_name' => $msg->sender?->name,
                        'text' => $msg->message,
                        'time' => $msg->created_at->timezone(auth()->user()->timezone ?? config('app.timezone', 'Europe/Madrid'))->format('d/m/Y H:i'),
                        'file_url' => $msg->file_url,
                        'file_name' => $msg->file_name,
                        'file_type' => $msg->file_type,
                        'storage_provider' => $msg->storage_provider,
                        'web_view_link' => $msg->web_view_link,
                        'parent_id' => $msg->parent_id,
                        'parent_text' => $msg->parent?->message,
                        'parent_sender_name' => $msg->parent?->sender?->name,
                        'call_room' => $msg->call_room,
                    ];
                })
            ]);
        } catch (\Throwable $e) {
            Log::error("ChatMessageController@index error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }


    /**
     * Crea y almacena un nuevo mensaje de chat (texto, archivo o enlace de Google Drive).
     *
     * Soporta archivos locales (almacenados en storage/app/public/chat_attachments)
     * y archivos de Google Drive (referenciados por webViewLink).
     * Envía una notificación al receptor si está offline.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener receiver_id, message (opcional), file o drive_file, parent_id (opcional), call_room (opcional)
     * @return \Illuminate\Http\JsonResponse Respuesta con el mensaje creado en formato normalizado
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required',
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
            'drive_file' => 'nullable|string',
            'parent_id' => 'nullable|exists:chat_messages,id',
            'call_room' => 'nullable|string',
        ]);

        $isGroup = str_starts_with($request->receiver_id, 'group_');
        $groupId = $isGroup ? (int) str_replace('group_', '', $request->receiver_id) : null;
        $receiverId = !$isGroup ? (int) $request->receiver_id : null;

        if ($receiverId && !\App\Models\User::where('id', $receiverId)->exists()) {
            return response()->json(['error' => 'Receiver not found'], 422);
        }

        $data = [
            'sender_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'chat_group_id' => $groupId,
            'message' => $request->message,
            'parent_id' => $request->parent_id,
            'call_room' => $request->call_room,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('chat_attachments', 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
            $data['file_size'] = $file->getSize();
            $data['storage_provider'] = 'local';
        } elseif ($request->filled('drive_file')) {
            $driveFile = json_decode($request->drive_file, true);
            $data['file_name'] = $driveFile['name'] ?? 'Google Drive File';
            $data['file_type'] = 'file';
            $data['storage_provider'] = 'google';
            $data['web_view_link'] = $driveFile['webViewLink'] ?? null;
        }

        $msg = ChatMessage::create($data);

        if (!$isGroup) {
            // Notify direct receiver
            $receiver = \App\Models\User::find($receiverId);
            if ($receiver && !$receiver->isOnline()) {
                $receiver->notify(new \App\Notifications\NewChatMessageNotification($msg));
            }
        } else {
            // Update last read for sender
            $group = \App\Models\ChatGroup::find($groupId);
            if ($group) {
                $group->users()->updateExistingPivot(auth()->id(), ['last_read_at' => now()]);
            }
            // TODO: Group notifications if needed
        }

        return response()->json([
            'message' => [
                'id' => $msg->id,
                'sender' => 'me',
                'sender_name' => auth()->user()->name,
                'text' => $msg->message,
                'time' => now()->format('d/m/Y H:i'),
                'file_url' => $msg->file_url,
                'file_name' => $msg->file_name,
                'file_type' => $msg->file_type,
                'storage_provider' => $msg->storage_provider,
                'web_view_link' => $msg->web_view_link,
                'parent_id' => $msg->parent_id,
                'call_room' => $msg->call_room,
            ]
        ]);
    }


    /**
     * Elimina todos los mensajes de una conversación individual o grupo.
     *
     * También borra los archivos adjuntos locales del almacenamiento.
     * Para grupos, verifica que el usuario sea miembro antes de proceder.
     *
     * @param  string  $identifier  ID del usuario o 'group_{id}' del chat a limpiar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true, o error 403/500
     */
    public function clear($identifier): JsonResponse
    {
        $userId = auth()->id();
        $isGroup = str_starts_with($identifier, 'group_');

        try {
            if ($isGroup) {
                $groupId = (int) str_replace('group_', '', $identifier);
                
                $group = \App\Models\ChatGroup::find($groupId);
                if (!$group || !$group->users()->where('user_id', $userId)->exists()) {
                    return response()->json(['success' => false, 'message' => 'No autorizado o grupo no encontrado'], 403);
                }

                $messages = ChatMessage::where('chat_group_id', $groupId)->get();
            } else {
                $receiverId = (int) $identifier;
                $messages = ChatMessage::where(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
                })->orWhere(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
                })->get();
            }

            foreach ($messages as $message) {
                if ($message->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($message->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($message->file_path);
                }
                $message->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Error clearing chat history: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al limpiar el historial'], 500);
        }
    }


    /**
     * Elimina un mensaje individual del chat.
     *
     * Solo el remitente puede eliminar su propio mensaje. En grupos,
     * verifica además que el usuario sea miembro del grupo.
     * Borra el archivo adjunto si existe.
     *
     * @param  int  $id  ID del mensaje a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true, o error 403/500
     */
    public function destroy($id): JsonResponse
    {
        $message = ChatMessage::findOrFail($id);

        if ($message->sender_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($message->chat_group_id) {
            $group = \App\Models\ChatGroup::find($message->chat_group_id);
            if ($group && !$group->users()->where('user_id', auth()->id())->exists()) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }
        }

        try {
            if ($message->file_path) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($message->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($message->file_path);
                }
            }
            $message->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Error deleting chat message: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar el mensaje'], 500);
        }
    }
}
