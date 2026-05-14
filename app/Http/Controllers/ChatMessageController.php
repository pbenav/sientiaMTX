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

            $messages = ChatMessage::where(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
                })
                ->orWhere(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $otherUser = User::find($receiverId);
            if (!$otherUser) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $commonTeam = $otherUser->teams()->whereHas('members', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->first();

            Log::info("ChatMessageController@index success", ['user' => $userId, 'receiver' => $receiverId, 'count' => $messages->count()]);
            return response()->json([
                'member' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'photo' => $otherUser->profile_photo_url,
                    'team' => $commonTeam ? $commonTeam->name : null,
                    'status' => $otherUser->getStatusInfo()['label'],
                ],
                'messages' => $messages->map(function ($msg) use ($userId) {
                    return [
                        'id' => $msg->id,
                        'sender' => $msg->sender_id === $userId ? 'me' : 'them',
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

            Log::info("ChatMessageController@check success", ['user' => $userId, 'count' => $unread->count()]);
            return response()->json(['unread' => $data]);
        } catch (\Throwable $e) {
            Log::error("ChatMessageController@check error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['unread' => [], 'error' => 'Internal Server Error'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
            'drive_file' => 'nullable|string',
            'parent_id' => 'nullable|exists:chat_messages,id',
            'call_room' => 'nullable|string',
        ]);

        $data = [
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
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

        Log::info("ChatMessageController@store success", ['user' => auth()->id(), 'msg_id' => $msg->id]);

        return response()->json([
            'message' => [
                'id' => $msg->id,
                'sender' => 'me',
                'text' => $msg->message,
                'time' => now()->format('H:i'),
                'file_url' => $msg->file_url,
                'file_name' => $msg->file_name,
                'file_type' => $msg->file_type,
                'storage_provider' => $msg->storage_provider,
                'web_view_link' => $msg->web_view_link,
                'parent_id' => $msg->parent_id,
            ]
        ]);
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
