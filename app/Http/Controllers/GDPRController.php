<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GDPRController extends Controller
{
    /**
     * Export all user data to a JSON file.
     */
    public function export()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $data = [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'timezone' => $user->timezone,
                'created_at' => $user->created_at,
                'gamification' => [
                    'experience_points' => $user->experience_points,
                    'resilience_points' => $user->resilience_points,
                    'energy_level' => $user->energy_level,
                ],
                'gdpr' => [
                    'privacy_policy_accepted_at' => $user->privacy_policy_accepted_at,
                    'terms_accepted_at' => $user->terms_accepted_at,
                    'marketing_accepted_at' => $user->marketing_accepted_at,
                ],
            ],
            'teams' => $user->teams()->get()->map(fn($team) => [
                'name' => $team->name,
                'role' => $user->getRole($team),
                'joined_at' => $team->pivot->created_at,
            ]),
            'assigned_tasks' => $user->assignedTasks()->get()->map(fn($task) => [
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'status' => $task->status,
                'due_date' => $task->due_date,
            ]),
            'created_tasks' => $user->createdTasks()->get()->map(fn($task) => [
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'created_at' => $task->created_at,
            ]),
            'time_logs' => $user->timeLogs()->get()->map(fn($log) => [
                'type' => $log->type,
                'task' => $log->task ? $log->task->title : null,
                'start_at' => $log->start_at,
                'end_at' => $log->end_at,
                'note' => $log->note,
            ]),
            'forum_messages' => \App\Models\ForumMessage::where('user_id', $user->id)->get()->map(fn($message) => [
                'content' => $message->content,
                'created_at' => $message->created_at,
                'thread' => $message->thread->title,
            ]),
            'chat_messages' => \App\Models\ChatMessage::where(function($query) use ($user) {
                    $query->where('sender_id', $user->id)
                          ->orWhere('receiver_id', $user->id);
                })
                ->get()
                ->map(fn($msg) => [
                    'id' => $msg->id,
                    'sender' => $msg->sender_id === $user->id ? 'me' : ($msg->sender ? $msg->sender->name : 'Unknown'),
                    'receiver' => $msg->receiver_id === $user->id ? 'me' : ($msg->receiver ? $msg->receiver->name : 'Unknown'),
                    'message' => $msg->message,
                    'call_room' => $msg->call_room,
                    'created_at' => $msg->created_at,
                ]),
            'quick_notes' => $user->quickNotes()->get()->map(fn($note) => [
                'title' => $note->title,
                'content' => $note->content,
                'color' => $note->color,
                'created_at' => $note->created_at,
            ]),
            'mood_logs' => $user->moodLogs()->get()->map(fn($log) => [
                'energy_level' => $log->energy_level,
                'mood_label' => $log->mood_label,
                'notes' => $log->notes,
                'created_at' => $log->created_at,
            ]),
            'kudos_received' => $user->receivedKudos()->get()->map(fn($kudo) => [
                'from' => $kudo->fromUser ? $kudo->fromUser->name : 'Unknown',
                'reason' => $kudo->reason,
                'created_at' => $kudo->created_at,
            ]),
            'kudos_given' => $user->givenKudos()->get()->map(fn($kudo) => [
                'to' => $kudo->toUser ? $kudo->toUser->name : 'Unknown',
                'reason' => $kudo->reason,
                'created_at' => $kudo->created_at,
            ]),
        ];

        $filename = 'sientia_mtx_data_' . $user->id . '_' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}
