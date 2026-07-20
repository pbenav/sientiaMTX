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
                'work_routine' => [
                    'start_time' => $user->work_start_time,
                    'end_time' => $user->work_end_time,
                    'location_lat' => $user->location_lat,
                    'location_lng' => $user->location_lng,
                ],
                'notifications' => $user->notification_settings,
                'telegram_username' => $user->telegram_username,
            ],
            'teams' => $user->teams()->get()->map(fn($team) => [
                'name' => $team->name,
                'role' => $user->getRole($team),
                'joined_at' => $team->pivot->created_at,
            ]),
            'skills' => $user->skills()->get()->map(fn($skill) => [
                'name' => $skill->name,
                'level' => $skill->pivot->level,
                'total_xp' => $skill->pivot->total_xp,
            ]),
            'ai_preferences' => $user->aiPreferences()->get()->map(fn($pref) => [
                'communication_style' => $pref->communication_style,
                'tone' => $pref->tone,
                'context_notes' => $pref->context_notes,
            ]),
            'assigned_tasks' => $user->assignedTasks()->get()->map(fn($task) => [
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'status' => $task->status,
                'visibility' => $task->visibility,
                'due_date' => $task->due_date,
            ]),
            'created_tasks' => $user->createdTasks()->get()->map(fn($task) => [
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'visibility' => $task->visibility,
                'created_at' => $task->created_at,
            ]),
            'assigned_activities' => \App\Models\Activity::whereHas('assignedTo', fn($q) => $q->where('users.id', $user->id))->get()->map(fn($act) => [
                'type' => $act->type,
                'title' => $act->title,
                'description' => $act->description,
                'status' => $act->status,
                'visibility' => $act->visibility,
                'created_at' => $act->created_at,
            ]),
            'created_activities' => \App\Models\Activity::where('created_by_id', $user->id)->get()->map(fn($act) => [
                'type' => $act->type,
                'title' => $act->title,
                'description' => $act->description,
                'status' => $act->status,
                'visibility' => $act->visibility,
                'created_at' => $act->created_at,
            ]),
            'expedientes' => \App\Models\Expediente::where(function($q) use ($user) {
                $q->where('created_by_id', $user->id)
                  ->orWhere('assigned_user_id', $user->id)
                  ->orWhereHas('assignedTo', fn($sub) => $sub->where('users.id', $user->id));
            })->get()->map(fn($exp) => [
                'code' => $exp->code,
                'title' => $exp->title,
                'status' => $exp->status,
                'visibility' => $exp->visibility,
                'role' => $exp->created_by_id === $user->id ? 'creator' : 'collaborator',
                'created_at' => $exp->created_at,
            ]),
            'appointments_managed' => $user->appointments()->with('visitor')->get()->map(fn($appt) => [
                'localizador' => $appt->localizador,
                'date' => $appt->appointment_date?->format('Y-m-d'),
                'time' => $appt->appointment_time,
                'status' => $appt->status,
                'client_name' => $appt->visitor?->full_name,
                'client_email' => $appt->visitor?->email,
            ]),
            'appointments_as_client' => \App\Models\Appointment::whereHas('visitor', fn($q) => $q->where('email', $user->email))->with('service')->get()->map(fn($appt) => [
                'localizador' => $appt->localizador,
                'date' => $appt->appointment_date?->format('Y-m-d'),
                'time' => $appt->appointment_time,
                'status' => $appt->status,
                'service' => $appt->service ? $appt->service->name : null,
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
            'ai_chat_messages' => $user->aiChatMessages()->get()->map(fn($msg) => [
                'id' => $msg->id,
                'message' => $msg->message,
                'is_user_message' => $msg->is_user_message,
                'created_at' => $msg->created_at,
            ]),
            'gamification_logs' => $user->gamificationLogs()->get()->map(fn($log) => [
                'event' => $log->event,
                'points' => $log->points,
                'description' => $log->description,
                'created_at' => $log->created_at,
            ]),
            'security_logs' => $user->securityLogs()->get()->map(fn($log) => [
                'action' => $log->action,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
            ]),
            'attachment_logs' => $user->attachmentLogs()->get()->map(fn($log) => [
                'action' => $log->action,
                'file_name' => $log->file_name,
                'file_type' => $log->file_type,
                'created_at' => $log->created_at,
            ]),
            'task_private_notes' => $user->taskPrivateNotes()->get()->map(fn($note) => [
                'content' => $note->content,
                'task_title' => $note->task ? $note->task->title : null,
                'created_at' => $note->created_at,
            ]),
            'activity_ratings' => \App\Models\ActivityRating::where('user_id', $user->id)->get()->map(fn($rating) => [
                'activity_title' => $rating->activity ? $rating->activity->title : null,
                'score' => $rating->score,
                'type' => $rating->type,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at,
            ]),
            'task_ratings' => $user->taskRatings()->get()->map(fn($rating) => [
                'task_title' => $rating->task ? $rating->task->title : null,
                'score' => $rating->score,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at,
            ]),
            'forum_threads' => $user->forumThreads()->get()->map(fn($thread) => [
                'title' => $thread->title,
                'content' => $thread->content,
                'created_at' => $thread->created_at,
            ]),
            'forum_messages' => $user->forumMessages()->get()->map(fn($message) => [
                'content' => $message->content,
                'thread_title' => $message->thread ? $message->thread->title : null,
                'created_at' => $message->created_at,
            ]),
            'appointment_blocks' => $user->appointmentBlocks()->get()->map(fn($block) => [
                'start_time' => $block->start_time,
                'end_time' => $block->end_time,
                'is_available' => $block->is_available,
                'created_at' => $block->created_at,
            ]),
            'appointment_services' => $user->appointmentServices()->get()->map(fn($service) => [
                'name' => $service->name,
                'duration' => $service->duration,
                'price' => $service->price,
                'created_at' => $service->created_at,
            ]),
            'appointment_schedules' => $user->appointmentSchedules()->get()->map(fn($schedule) => [
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'is_available' => $schedule->is_available,
                'created_at' => $schedule->created_at,
            ]),
            'invitations' => $user->invitations()->get()->map(fn($invitation) => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'status' => $invitation->status,
                'created_at' => $invitation->created_at,
            ]),
            'task_histories' => $user->taskHistories()->get()->map(fn($history) => [
                'task_title' => $history->task ? $history->task->title : null,
                'action' => $history->action,
                'old_values' => $history->old_values,
                'new_values' => $history->new_values,
                'notes' => $history->notes,
                'created_at' => $history->created_at,
            ]),
            'activity_histories' => \App\Models\ActivityHistory::where('user_id', $user->id)->get()->map(fn($history) => [
                'activity_title' => $history->activity ? $history->activity->title : null,
                'action' => $history->action,
                'old_values' => $history->old_values,
                'new_values' => $history->new_values,
                'notes' => $history->notes,
                'created_at' => $history->created_at,
            ]),
            'task_assignments' => $user->taskAssignments()->get()->map(fn($assignment) => [
                'task_title' => $assignment->task ? $assignment->task->title : null,
                'assigned_at' => $assignment->assigned_at,
                'assigned_by' => $assignment->assignedBy ? $assignment->assignedBy->name : null,
                'created_at' => $assignment->created_at,
            ]),
        ];

        $filename = 'sientia_mtx_data_' . $user->id . '_' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Procesar la solicitud de borrado definitivo y derecho al olvido (Artículo 17 GDPR).
     */
    public function erasure(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Limpiar o anonimizar relaciones directas
        $user->quickNotes()->delete();
        $user->moodLogs()->delete();
        $user->timeLogs()->delete();
        $user->aiPreferences()->delete();
        $user->skills()->detach();
        $user->teams()->detach();
        
        \App\Models\Activity::where('created_by_id', $user->id)->update(['created_by_id' => null, 'description' => \DB::raw('CONCAT(description, "\n\n[Creador original anonimizado por GDPR]")')]);
        \DB::table('activity_assignments')->where('user_id', $user->id)->delete();

        // 2. Anonimizar citas previas gestionadas
        \App\Models\Appointment::where('user_id', $user->id)->update([
            'user_id' => null,
            'member_notes' => 'Usuario gestor eliminado por GDPR.'
        ]);

        // 3. Anonimizar citas como cliente
        \App\Models\AppointmentVisitor::where('email', $user->email)->update([
            'email' => 'gdpr-deleted-' . $user->id . '@sientia.local',
            'first_name' => 'Usuario Eliminado',
            'last_name' => '(GDPR)'
        ]);

        // 4. Borrar mensajes de chat
        \App\Models\ChatMessage::where('sender_id', $user->id)->delete();

        // 5. Anonimizar borrar datos adicionales del usuario
        $user->aiChatMessages()->delete();
        $user->taskPrivateNotes()->delete();
        $user->securityLogs()->delete();
        $user->attachmentLogs()->delete();
        \App\Models\ActivityRating::where('user_id', $user->id)->delete();
        $user->taskRatings()->delete();
        $user->forumThreads()->delete();
        $user->forumMessages()->delete();
        $user->appointmentBlocks()->delete();
        $user->appointmentServices()->delete();
        $user->appointmentSchedules()->delete();
        $user->invitations()->delete();
        $user->taskHistories()->delete();
        \App\Models\ActivityHistory::where('user_id', $user->id)->delete();
        $user->taskAssignments()->delete();

        // 6. Revocar sesión y borrar usuario
        auth()->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Tu cuenta y tus datos personales han sido eliminados de acuerdo al Artículo 17 del GDPR (Derecho al olvido).');
    }
}
