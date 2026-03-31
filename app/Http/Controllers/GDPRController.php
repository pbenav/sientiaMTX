<?php

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
            'forum_messages' => \App\Models\ForumMessage::where('user_id', $user->id)->get()->map(fn($message) => [
                'content' => $message->content,
                'created_at' => $message->created_at,
                'thread' => $message->thread->title,
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
