<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatPresenceController extends Controller
{
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
            Log::error("ChatPresenceController@check error: " . $e->getMessage());
            return response()->json(['unread' => [], 'error' => 'Internal Server Error'], 500);
        }
    }

    public function getUsers(): JsonResponse
    {
        $user = auth()->user();
        
        // Return all approved users in the system
        $users = User::where('id', '!=', $user->id)
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
}
