<?php

namespace App\Traits;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskHistory;
use App\Models\TaskTag;
use App\Models\TimeLog;
use App\Models\ForumThread;
use App\Models\TaskAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait ManagesTaskDeletion
{
    /**
     * Deeply purge a task and all its related data from the database and storage.
     */
    protected function deepPurgeTask(Task $task): void
    {
        DB::transaction(function () use ($task) {
            // 1. Tags
            TaskTag::where('task_id', $task->id)->delete();

            // 2. Assignments
            TaskAssignment::where('task_id', $task->id)->delete();

            // 3. History
            TaskHistory::where('task_id', $task->id)->delete();

            // 4. Time Logs
            TimeLog::where('task_id', $task->id)->delete();

            // 5. Forum
            ForumThread::where('task_id', $task->id)->delete();

            // 6. Attachments (DB and Files)
            $attachments = TaskAttachment::where('task_id', $task->id)->get();
            foreach ($attachments as $attachment) {
                if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }

            // 7. Pivot Skill-Task
            DB::table('skill_task')->where('task_id', $task->id)->delete();

            // 8. Finally, the task itself (Force Delete)
            $task->forceDelete();
        });
    }
}
