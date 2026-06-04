<?php

namespace App\Observers;

use App\Models\Team;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TelegramMessage;

class TeamObserver
{
    /**
     * Handle the Team "forceDeleting" event.
     */
    public function forceDeleting(Team $team): void
    {
        DB::transaction(function () use ($team) {
            // 1. Delete tasks and their physical attachments
            foreach ($team->tasks()->withTrashed()->get() as $task) {
                foreach ($task->attachments as $attachment) {
                    if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                    $attachment->delete(); // Delete the record
                }
                $task->forceDelete();
            }

            // 2. Delete forum threads and messages with attachments
            foreach ($team->forumThreads as $thread) {
                foreach ($thread->messages as $message) {
                    foreach ($message->attachments as $attachment) {
                        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                            Storage::disk('public')->delete($attachment->file_path);
                        }
                        $attachment->delete();
                    }
                    $message->delete();
                }
                $thread->delete();
            }

            // 3. Delete expedientes and their attachments
            foreach ($team->expedientes as $expediente) {
                foreach ($expediente->attachments as $attachment) {
                    if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                    $attachment->delete();
                }
                $expediente->delete();
            }

            // 4. Delete Telegram media
            $telegramMessages = TelegramMessage::where('team_id', $team->id)->get();
            foreach ($telegramMessages as $msg) {
                $path = $msg->photo_path ?: ($msg->voice_path ?: $msg->sticker_path);
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $msg->delete();
            }

            // 5. Surveys, Groups, Skills, Kanban, Invitations, Events, Services, TimeLogs
            foreach ($team->surveys as $survey) {
                // Deep delete survey relations if not handled by database cascade
                foreach ($survey->questions as $question) {
                    $question->options()->delete();
                    $question->votes()->delete();
                    $question->delete();
                }
                $survey->delete();
            }

            $team->groups()->delete();
            $team->skills()->delete();
            $team->kanbanColumns()->delete();
            $team->invitations()->delete();
            $team->calendarEvents()->delete();
            $team->telegramGroupMembers()->delete();
            
            // Purge Services and their reports
            foreach ($team->services as $service) {
                $service->reports()->delete();
                $service->delete();
            }

            // Purge Time Logs associated with the team's tasks
            \App\Models\TimeLog::whereIn('task_id', $team->tasks()->withTrashed()->pluck('id'))->delete();

            // 6. Detach members
            $team->members()->detach();
        });
    }
}
