<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para la gestión de grupos de chat (creación, renombrado, eliminación, miembros).
 */
class ChatGroupController extends Controller
{
    /**
     * Crea un nuevo grupo de chat con los participantes especificados.
     *
     * El creador se añade automáticamente como miembro con last_read_at establecido.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener receiver_ids (array obligatorio) y name (opcional, máx 100)
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y datos del grupo creado, o error con código 422/500
     */
    public function createGroup(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_ids' => 'required|array',
            'receiver_ids.*' => 'exists:users,id',
            'name' => 'nullable|string|max:100',
        ]);

        $userId = auth()->id();
        $userIds = collect($request->receiver_ids)->push($userId)->unique()->values();

        if ($userIds->count() < 2) {
            return response()->json(['success' => false, 'message' => 'Not enough participants'], 422);
        }

        try {
            $group = ChatGroup::create([
                'name' => $request->name ?? ('Grupo de ' . $userIds->count() . ' miembros'),
                'created_by' => $userId
            ]);

            $syncData = [];
            foreach ($userIds as $uid) {
                $syncData[$uid] = ['last_read_at' => $uid === $userId ? now() : null];
            }
            $group->users()->sync($syncData);

            Log::info('createGroup: group created', ['group_id' => $group->id, 'participants' => $userIds->count()]);

            return response()->json([
                'success' => true, 
                'group' => [
                    'id' => 'group_' . $group->id,
                    'name' => $group->name,
                    'photo' => $group->avatar,
                    'status' => $userIds->count() . ' participantes'
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('createGroup error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear grupo'], 500);
        }
    }

    /**
     * Obtiene la lista de grupos recientes del usuario con su último mensaje.
     *
     * Retorna los grupos ordenados por última actividad, incluyendo el último mensaje
     * enviado en cada grupo y la lista de nombres de participantes.
     *
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y array de grupos con metadatos
     */
    public function getRecentGroups(): JsonResponse
    {
        $user = auth()->user();
        
        $groups = $user->chatGroups()
            ->with(['users', 'messages' => function($q) {
                $q->latest()->limit(1);
            }, 'messages.sender'])
            ->get()
            ->map(function($group) use ($user) {
                $lastMessage = $group->messages->first();
                $lastActive = $lastMessage ? $lastMessage->created_at : $group->created_at;
                
                $namesList = $group->users->map(function($u) use ($user) {
                    return $u->id === $user->id ? 'Tú' : explode(' ', trim($u->name))[0];
                });
                $me = $namesList->filter(fn($n) => $n === 'Tú');
                $others = $namesList->filter(fn($n) => $n !== 'Tú');
                $statusString = $me->merge($others)->implode(', ') . ' (' . $group->users->count() . ')';

                return [
                    'id' => 'group_' . $group->id,
                    'name' => $group->name,
                    'photo' => $group->avatar,
                    'status' => $statusString,
                    'last_active' => $lastActive,
                    'last_message' => $lastMessage ? [
                        'text' => $lastMessage->message,
                        'time' => $lastMessage->created_at->timezone($user->timezone ?? 'Europe/Madrid')->format('d/m/Y H:i'),
                        'sender_name' => $lastMessage->sender?->name,
                    ] : null,
                ];
            })
            ->sortByDesc('last_active')
            ->values();

        return response()->json(['success' => true, 'groups' => $groups]);
    }

    /**
     * Renombra un grupo de chat.
     *
     * Solo los miembros del grupo pueden renombrarlo.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener name (obligatorio, máx 100)
     * @param  int  $groupId  ID del grupo a renombrar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y nuevo nombre, o error 403
     */
    public function renameGroup(Request $request, $groupId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $group = ChatGroup::findOrFail($groupId);
        
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $group->name = $request->name;
        $group->save();

        return response()->json([
            'success' => true,
            'name' => $group->name
        ]);
    }

    /**
     * Elimina un grupo de chat y todos sus mensajes adjuntos.
     *
     * Solo los miembros del grupo pueden eliminarlo. Borra los archivos adjuntos
     * del almacenamiento antes de eliminar los mensajes y el grupo.
     *
     * @param  int  $groupId  ID del grupo a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true, o error 403
     */
    public function deleteGroup($groupId): JsonResponse
    {
        $group = ChatGroup::findOrFail($groupId);
        
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $messages = $group->messages;
        foreach ($messages as $message) {
            if ($message->file_path) {
                try {
                    if (\Illuminate\Support\Facades\Storage::exists($message->file_path)) {
                        \Illuminate\Support\Facades\Storage::delete($message->file_path);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Error al borrar archivo adjunto al eliminar grupo: " . $e->getMessage());
                }
            }
            $message->delete();
        }

        $group->users()->detach();
        $group->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Añade un nuevo miembro a un grupo de chat.
     *
     * Solo los miembros actuales del grupo pueden añadir participantes.
     * Crea un mensaje de sistema notificando la incorporación del nuevo miembro.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener user_id (obligatorio, debe existir)
     * @param  int  $groupId  ID del grupo al que añadir el miembro
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y nuevo conteo de participantes, o error 403
     */
    public function addGroupMember(Request $request, $groupId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group = ChatGroup::findOrFail($groupId);

        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'No estás autorizado para modificar este grupo.'], 403);
        }

        $userIdToAdd = $request->user_id;

        if (!$group->users()->where('user_id', $userIdToAdd)->exists()) {
            $group->users()->attach($userIdToAdd, ['last_read_at' => null]);
            
            $addedUser = \App\Models\User::find($userIdToAdd);
            \App\Models\ChatMessage::create([
                'sender_id' => auth()->id(),
                'chat_group_id' => $group->id,
                'message' => '👋 ha añadido a ' . $addedUser->name . ' al grupo.',
            ]);

            Log::info('addGroupMember: member added', ['group_id' => $group->id, 'added_user' => $userIdToAdd]);

            return response()->json([
                'success' => true,
                'status' => $group->users()->count() . ' participantes'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'El usuario ya es miembro del grupo']);
    }
}
