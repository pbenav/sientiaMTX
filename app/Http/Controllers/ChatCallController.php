<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatGroup;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatCallController extends Controller
{
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
                $group = ChatGroup::with('users')->find($groupId);
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
            $group = ChatGroup::with('users')->find($groupId);
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
            $attendeeEmails = User::whereIn('id', $receivers)->pluck('email')->toArray();
            $meetUri = $googleService->createMeetSpace($attendeeEmails);

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
