<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatMessageController extends Controller
{
    /**
     * Fetch messages between authenticated user and another user.
     */
    public function index($receiverId): JsonResponse
    {
        \Log::info('ChatMessageController@index starting for user: ' . auth()->id() . ' with receiver: ' . $receiverId);
        try {
            $userId = auth()->id();
            $receiverId = (int) $receiverId;

            // Mark incoming messages as read
            ChatMessage::where('sender_id', $receiverId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = ChatMessage::where(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $userId)
                          ->where('receiver_id', $receiverId);
                })
                ->orWhere(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $receiverId)
                          ->where('receiver_id', $userId);
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
                        'call_room' => $msg->call_room,
                        'file_name' => $msg->file_name,
                        'file_type' => $msg->file_type,
                        'file_url' => $msg->file_url,
                        'storage_provider' => $msg->storage_provider,
                        'web_view_link' => $msg->web_view_link,
                        'time' => $msg->created_at->timezone(auth()->user()->timezone ?? config('app.timezone', 'Europe/Madrid'))->format('H:i'),
                        'parent_id' => $msg->parent_id,
                        'parent_text' => $msg->parent?->message ?? ($msg->parent?->file_name ? '📎 '. $msg->parent->file_name : null),
                        'parent_sender_name' => $msg->parent?->sender?->name,
                    ];
                })
            ];

            \Log::info('ChatMessageController@index success for user: ' . $userId);
            return response()->json($data);
        } catch (\Throwable $e) {
            \Log::error('ChatMessageController@index error: ' . $e->getMessage(), [
                'userId' => auth()->id(),
                'receiverId' => $receiverId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a direct message.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required_without_all:call_room,file,drive_file|string|nullable',
            'call_room' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // Max 10MB
            'drive_file' => 'nullable|string', // JSON string
            'parent_id' => 'nullable|exists:chat_messages,id',
        ]);

        $fileName = null;
        $filePath = null;
        $fileType = null;
        $fileSize = null;
        $storageProvider = 'local';
        $webViewLink = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $fileName = $file->getClientOriginalName();
            
            if (str_contains($mime, 'image/')) {
                $fileType = 'image';
            } elseif (str_contains($mime, 'audio/')) {
                $fileType = 'audio';
            } else {
                $fileType = 'file';
            }

            $filePath = $file->store('chat/attachments', 'public');
            $fileSize = $file->getSize();
        } 
        elseif ($request->filled('drive_file')) {
            $driveData = json_decode($request->drive_file, true);
            if ($driveData) {
                $fileName = $driveData['name'] ?? 'Archivo Drive';
                $filePath = 'google_drive/' . ($driveData['id'] ?? time());
                $fileType = 'file';
                $fileSize = $driveData['size'] ?? 0;
                $storageProvider = 'google';
                $webViewLink = $driveData['webViewLink'] ?? '#';
            }
        }

        $msg = ChatMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'call_room' => $request->call_room,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'storage_provider' => $storageProvider,
            'web_view_link' => $webViewLink,
            'parent_id' => $request->parent_id,
        ]);

        $msg->load('parent.sender');

        return response()->json([
            'message' => [
                'id' => $msg->id,
                'sender' => 'me',
                'text' => $msg->message,
                'call_room' => $msg->call_room,
                'file_name' => $msg->file_name,
                'file_type' => $msg->file_type,
                'file_url' => $msg->file_url,
                'storage_provider' => $msg->storage_provider,
                'web_view_link' => $msg->web_view_link,
                'time' => $msg->created_at->timezone(auth()->user()->timezone ?? config('app.timezone', 'Europe/Madrid'))->format('H:i'),
                'parent_id' => $msg->parent_id,
                'parent_text' => $msg->parent?->message ?? ($msg->parent?->file_name ? '📎 '. $msg->parent->file_name : null),
                'parent_sender_name' => $msg->parent?->sender?->name,
            ]
        ]);
    }

    /**
     * Generate and invite another user to a video call room.
     */
    public function startCall(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $sender = auth()->user();
        $receiverId = $request->receiver_id;
        $roomName = 'sientia-mtx-call-' . min($sender->id, $receiverId) . '-' . max($sender->id, $receiverId) . '-' . time();

        $msg = ChatMessage::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'message' => '🎥 Te invito a una videoconferencia en vivo.',
            'call_room' => $roomName,
        ]);

        return response()->json([
            'room' => $roomName,
            'message' => [
                'id' => $msg->id,
                'sender' => 'me',
                'text' => $msg->message,
                'call_room' => $msg->call_room,
                'time' => $msg->created_at->timezone($sender->timezone ?? config('app.timezone', 'Europe/Madrid'))->format('H:i'),
            ]
        ]);
    }

    /**
     * Poll to check for any unread messages or active/incoming calls.
     */
    public function check(): JsonResponse
    {
        \Log::info('ChatMessageController@check starting for user: ' . auth()->id());
        try {
            $userId = auth()->id();
            $user = auth()->user();

            $unread = ChatMessage::where('receiver_id', $userId)
                ->where('is_read', false)
                ->with(['sender', 'parent.sender'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $unread->map(function ($msg) use ($userId, $user) {
                // Safe team lookup
                $commonTeam = null;
                if ($msg->sender) {
                    $commonTeam = $msg->sender->teams()
                        ->whereHas('users', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })->first();
                }

                $tz = $user->timezone ?? config('app.timezone', 'Europe/Madrid');

                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender?->name ?? 'Usuario',
                    'sender_photo' => $msg->sender?->profile_photo_url ?? asset('img/default-avatar.png'),
                    'sender_team' => $commonTeam?->name,
                    'text' => $msg->message,
                    'call_room' => $msg->call_room,
                    'file_name' => $msg->file_name,
                    'file_type' => $msg->file_type,
                    'file_url' => $msg->file_url,
                    'storage_provider' => $msg->storage_provider,
                    'web_view_link' => $msg->web_view_link,
                    'time' => $msg->created_at ? $msg->created_at->timezone($tz)->format('H:i') : now()->format('H:i'),
                    'parent_id' => $msg->parent_id,
                    'parent_text' => $msg->parent?->message ?? ($msg->parent?->file_name ? '📎 '. $msg->parent->file_name : null),
                    'parent_sender_name' => $msg->parent?->sender?->name,
                ];
            });

            \Log::info('ChatMessageController@check success for user: ' . $userId . ' (Count: ' . $unread->count() . ')');

            return response()->json([
                'unread' => $data
            ]);
        } catch (\Throwable $e) {
            \Log::error('ChatMessageController@check error: ' . $e->getMessage(), [
                'userId' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'unread' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all chat messages between authenticated user and receiver.
     */
    public function clear(int $receiverId): JsonResponse
    {
        $userId = auth()->id();
        ChatMessage::where(function ($query) use ($userId, $receiverId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($userId, $receiverId) {
                $query->where('sender_id', $receiverId)
                      ->where('receiver_id', $userId);
            })
            ->delete();

        return response()->json(['success' => true]);
    }
}
