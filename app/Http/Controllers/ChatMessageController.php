<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatMessageController extends Controller
{
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
                        'time' => $msg->created_at->timezone(auth()->user()->timezone ?? config('app.timezone', 'Europe/Madrid'))->format('H:i'),
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
     * Real-presence ping endpoint.
     * Only called by the frontend when the user has genuinely interacted with the app
     * (mouse movement, keypress, click, scroll) within the idle threshold.
     * This is the ONLY source of truth for last_activity_at.
     */
    public function presence(): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['ok' => false], 401);
        }

        $user->last_activity_at = now();
        $user->last_ip = request()->ip();
        if (!$user->last_login_at) {
            $user->last_login_at = now();
        }
        $user->save();

        return response()->json(['ok' => true]);
    }

    public function check(): JsonResponse
    {
        $user = auth()->user();
        $userId = $user->id;

        try {
            // Unread Direct Messages
            $unreadDirect = ChatMessage::where('receiver_id', $userId)
                ->where('is_read', false)
                ->with(['sender'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Unread Group Messages
            $userGroups = $user->chatGroups()->withPivot('last_read_at')->get();
            $unreadGroups = collect();
            
            foreach ($userGroups as $group) {
                $lastRead = $group->pivot->last_read_at;
                
                $query = ChatMessage::where('chat_group_id', $group->id)
                    ->where('sender_id', '!=', $userId)
                    ->with(['sender']);
                    
                if ($lastRead) {
                    $query->where('created_at', '>', $lastRead);
                }
                
                $unreadGroups = $unreadGroups->merge($query->get());
            }

            $unread = $unreadDirect->merge($unreadGroups)->sortByDesc('created_at');

            $data = $unread->map(function ($msg) {
                return [
                    'id'           => $msg->id,
                    'sender_id'    => $msg->chat_group_id ? 'group_' . $msg->chat_group_id : $msg->sender_id,
                    'sender_name'  => $msg->chat_group_id ? ($msg->group?->name ?? 'Chat Grupal') : ($msg->sender?->name ?? 'Usuario'),
                    'sender_photo' => $msg->chat_group_id ? 'https://ui-avatars.com/api/?name=Grupo&color=10b981&background=ecfdf5' : $msg->sender?->profile_photo_url,
                    'sender_team'  => null,
                    'text'         => ($msg->chat_group_id ? $msg->sender?->name . ': ' : '') . $msg->message,
                    'file_name'    => $msg->file_name,
                    'call_room'    => $msg->call_room,
                    'time'         => $msg->created_at ? $msg->created_at->timezone(auth()->user()->timezone ?? 'Europe/Madrid')->format('H:i') : now()->format('H:i'),
                ];
            });

            return response()->json(['unread' => $data->values()]);
        } catch (\Throwable $e) {
            Log::error("ChatMessageController@check error: " . $e->getMessage());
            return response()->json(['unread' => [], 'error' => 'Internal Server Error'], 500);
        }
    }

    public function getUsers(): JsonResponse
    {
        $user = auth()->user();
        
        // Return all approved users in the system
        $users = \App\Models\User::where('id', '!=', $user->id)
        ->where('is_approved', true)
        ->select('id', 'name', 'profile_photo_path', 'email')
        ->orderBy('name')
        ->get()
        ->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'photo' => $u->profile_photo_url,
                'email' => $u->email,
            ];
        });

        return response()->json(['users' => $users]);
    }

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
                'time' => now()->format('H:i'),
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
            // Find existing exact match group or create new one
            // Simple approach: just create a new group
            $group = \App\Models\ChatGroup::create([
                'name' => $request->name ?? ('Grupo de ' . $userIds->count() . ' miembros'),
                'created_by' => $userId
            ]);

            // Sync users with last_read_at initialized to now for the creator, null for others
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
                        'time' => $lastMessage->created_at->timezone($user->timezone ?? 'Europe/Madrid')->format('H:i'),
                        'sender_name' => $lastMessage->sender?->name,
                    ] : null,
                ];
            })
            ->sortByDesc('last_active')
            ->values();

        return response()->json(['success' => true, 'groups' => $groups]);
    }

    public function renameGroup(Request $request, $groupId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $group = \App\Models\ChatGroup::findOrFail($groupId);
        
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


    public function addGroupMember(Request $request, $groupId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group = \App\Models\ChatGroup::findOrFail($groupId);

        // Ensure current user is in the group
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'No estás autorizado para modificar este grupo.'], 403);
        }

        $userIdToAdd = $request->user_id;

        if (!$group->users()->where('user_id', $userIdToAdd)->exists()) {
            // Add user to the group
            $group->users()->attach($userIdToAdd, ['last_read_at' => null]);
            
            // Add a system message notifying about the new member
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

    public function startCall(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required',
            'receiver_ids' => 'nullable|array',
            'receiver_ids.*' => 'exists:users,id',
        ]);

        try {
            $isGroup = str_starts_with($request->receiver_id, 'group_');
            $groupId = $isGroup ? (int) str_replace('group_', '', $request->receiver_id) : null;
            
            $receivers = [];
            if ($isGroup) {
                $group = \App\Models\ChatGroup::with('users')->find($groupId);
                if ($group) {
                    $receivers = $group->users->pluck('id')->reject(fn($id) => $id == auth()->id())->toArray();
                }
            } else {
                $receivers = $request->filled('receiver_ids') ? $request->receiver_ids : [$request->receiver_id];
            }

            // Generate a unique Jitsi room name
            $room = 'sientia-' . auth()->id() . '-' . implode('-', array_slice($receivers, 0, 3)) . '-' . now()->timestamp;

            if ($isGroup) {
                ChatMessage::create([
                    'sender_id'   => auth()->id(),
                    'chat_group_id' => $groupId,
                    'message'     => '📞 Llamada de Jitsi Meet',
                    'call_room'   => $room,
                ]);
            } else {
                foreach ($receivers as $receiverId) {
                    ChatMessage::create([
                        'sender_id'   => auth()->id(),
                        'receiver_id' => $receiverId,
                        'message'     => '📞 Te está llamando por Jitsi Meet',
                        'call_room'   => $room,
                    ]);
                }
            }

            Log::info('startCall: room created', ['room' => $room, 'receivers' => count($receivers)]);

            return response()->json(['success' => true, 'room' => $room]);
        } catch (\Throwable $e) {
            Log::error('startCall error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear la sala'], 500);
        }
    }

    public function clear($identifier): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    public function startGoogleMeet(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required',
            'receiver_ids' => 'nullable|array',
            'receiver_ids.*' => 'exists:users,id',
        ]);

        $user = auth()->user();
        
        $isGroup = str_starts_with($request->receiver_id, 'group_');
        $groupId = $isGroup ? (int) str_replace('group_', '', $request->receiver_id) : null;

        $receivers = [];
        if ($isGroup) {
            $group = \App\Models\ChatGroup::with('users')->find($groupId);
            if ($group) {
                $receivers = $group->users->pluck('id')->reject(fn($id) => $id == $user->id)->toArray();
            }
        } else {
            $receivers = $request->filled('receiver_ids') ? $request->receiver_ids : [$request->receiver_id];
        }

        if (empty($receivers)) {
            return response()->json(['success' => false, 'message' => 'No hay participantes para iniciar la reunión'], 422);
        }

        // Find a team shared with the first receiver to get the Google token
        $receiver = User::find($receivers[0]);
        $sharedTeam = $receiver->teams()->whereHas('members', fn($q) => $q->where('user_id', $user->id))->first();
        $teamId = $sharedTeam?->id;

        $googleService = app(GoogleService::class);

        if (!$googleService->setTokenForUser($user, $teamId)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes Google vinculado. Conecta tu cuenta en Perfil → Integraciones.',
                'needs_auth' => true,
            ], 403);
        }

        try {
            $meetUri = $googleService->createMeetSpace();

            if (!$meetUri) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo crear la sala de Meet. Reconecta tu cuenta de Google para autorizar el nuevo permiso.',
                    'needs_auth' => true,
                ], 503);
            }

            if ($isGroup) {
                ChatMessage::create([
                    'sender_id'   => $user->id,
                    'chat_group_id' => $groupId,
                    'message'     => '🌐 Invitación de Google Meet',
                    'call_room'   => $meetUri,
                ]);
            } else {
                foreach ($receivers as $receiverId) {
                    ChatMessage::create([
                        'sender_id'   => $user->id,
                        'receiver_id' => $receiverId,
                        'message'     => '🌐 Te invita a una reunión de Google Meet',
                        'call_room'   => $meetUri,
                    ]);
                }
            }

            Log::info('startGoogleMeet: Meet space created', ['uri' => $meetUri, 'receivers' => count($receivers)]);

            return response()->json(['success' => true, 'meet_url' => $meetUri]);
        } catch (\Throwable $e) {
            Log::error('startGoogleMeet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear la sala de Meet'], 500);
        }
    }
}
