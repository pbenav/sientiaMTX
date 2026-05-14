<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatMessageController extends Controller
{
    public function index($receiverId): JsonResponse
    {
        $userId = auth()->id();
        Log::info("ChatMessageController@index starting", ['user' => $userId, 'receiver' => $receiverId]);

        try {
            $receiverId = (int) $receiverId;
            
            // Mark as read
            ChatMessage::where('sender_id', $receiverId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

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
            $commonTeam = $otherUser ? $otherUser->teams()->whereHas('users', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->first() : null;

            $data = [
                'member' => $otherUser ? [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'photo' => $otherUser->profile_photo_url,
                    'team' => $commonTeam ? $commonTeam->name : null,
                    'status' => $otherUser->getStatusInfo()['label'],
                ] : null,
                'messages' => $messages->map(function ($msg) use ($userId) {
                    return [
                        'id' => $msg->id,
                        'sender' => $msg->sender_id === $userId ? 'me' : 'them',
                        'text' => $msg->message,
                        'time' => $msg->created_at->timezone(auth()->user()->timezone ?? 'Europe/Madrid')->format('H:i'),
                    ];
                })
            ];

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error("ChatMessageController@index error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function check(): JsonResponse
    {
        $userId = auth()->id();
        Log::info("ChatMessageController@check starting", ['user' => $userId]);

        try {
            $unread = ChatMessage::where('receiver_id', $userId)
                ->where('is_read', false)
                ->with(['sender'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $unread->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender?->name ?? 'Usuario',
                    'text' => $msg->message,
                    'time' => $msg->created_at ? $msg->created_at->timezone(auth()->user()->timezone ?? 'Europe/Madrid')->format('H:i') : now()->format('H:i'),
                ];
            });

            return response()->json(['unread' => $data]);
        } catch (\Throwable $e) {
            Log::error("ChatMessageController@check error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['unread' => [], 'error' => 'Internal Server Error'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['receiver_id' => 'required|exists:users,id', 'message' => 'required|string']);
        $msg = ChatMessage::create(['sender_id' => auth()->id(), 'receiver_id' => $request->receiver_id, 'message' => $request->message]);
        return response()->json(['message' => ['id' => $msg->id, 'sender' => 'me', 'text' => $msg->message, 'time' => now()->format('H:i')]]);
    }

    public function startCall(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not implemented in debug mode']);
    }

    public function clear(int $receiverId): JsonResponse
    {
        return response()->json(['success' => true]);
    }
}
