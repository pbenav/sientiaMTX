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
    public function index(int $receiverId): JsonResponse
    {
        $userId = auth()->id();

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
            ->with(['parent.sender'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
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
        ]);
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
        $userId = auth()->id();

        // Find unread messages
        $unread = ChatMessage::where('receiver_id', $userId)
            ->where('is_read', false)
            ->with('parent.sender')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'unread' => $unread->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender?->name ?? 'Usuario Desconocido',
                    'sender_photo' => $msg->sender?->profile_photo_url ?? asset('img/default-avatar.png'),
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
        ]);
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
