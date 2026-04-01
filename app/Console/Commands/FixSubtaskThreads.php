<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ForumThread;
use App\Models\Task;

class FixSubtaskThreads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sientia:fix-subtask-threads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates forum threads incorrectly assigned to subtasks recursively up to their root parent task.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding forum threads assigned to subtasks...');

        // Find all threads that have a task_id
        $threads = ForumThread::whereNotNull('task_id')->with('task')->get();
        $fixedCount = 0;

        foreach ($threads as $thread) {
            $task = $thread->task;
            
            // Skip if task no longer exists or if it's already a root task
            if (!$task || !$task->parent_id) {
                continue;
            }
            
            $originalTaskId = $task->id;

            // Find root task
            $rootTask = $task;
            while ($rootTask->parent_id && $rootTask->parent) {
                $rootTask = $rootTask->parent;
            }

            // At this point $rootTask is guaranteed to be a root task (parent_id is null)
            $this->info("Found subtask thread '{$thread->title}' (Task ID: {$originalTaskId}). Root task is {$rootTask->id}.");

            // Check if the root task ALREADY has a thread
            $existingRootThread = ForumThread::where('task_id', $rootTask->id)->where('id', '!=', $thread->id)->first();

            if ($existingRootThread) {
                // Root task already has a thread. We need to move all messages to the existing thread.
                $this->info("  -> Root task already has a thread (ID: {$existingRootThread->id}). Moving messages...");
                
                // Move messages
                foreach ($thread->messages as $message) {
                    $message->update(['forum_thread_id' => $existingRootThread->id]);
                }
                
                // Delete the orphaned thread
                $thread->delete();
                $this->info("  -> Moved messages and deleted duplicate thread.");
            } else {
                // Root task does NOT have a thread. REASSIGN this thread to the root task.
                $this->info("  -> Reassigning thread to root Task {$rootTask->id}.");
                $thread->update(['task_id' => $rootTask->id]);
            }
            
            $fixedCount++;
        }

        $this->info("Operation completed. Fixed {$fixedCount} subtask threads.");
    }
}
