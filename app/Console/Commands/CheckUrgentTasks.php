<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use App\Notifications\TaskSummaryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckUrgentTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-urgent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for urgent tasks approaching their due date and notify assigned users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for urgent tasks with summary logic and quotes...');

        // 1. Collect all users and their tasks that need notification
        $userTasks = [];

        // Identificamos tareas:
        // 1. No completadas ni canceladas
        // 2. Con urgencia 'high' o 'critical'
        
        $tasks = Task::with('assignedTo')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereIn('urgency', ['high', 'critical'])
            ->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->get();

        foreach ($tasks as $task) {
            foreach ($task->assignedTo as $user) {
                $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
                $leadHours = (int) ($settings['notify_before_hours'] ?? 24);
                
                // Si la tarea vence dentro del rango de antelación del usuario
                if ($task->due_date->diffInHours(now()) <= $leadHours) {
                    $metadata = $task->metadata ?? [];
                    $lastNotified = $metadata['last_reminder_sent_at'] ?? null;

                    // Evitamos duplicar recordatorios en menos de 12 horas
                    if (!$lastNotified || now()->parse($lastNotified)->addHours(12)->isPast()) {
                        $userTasks[$user->id]['user'] = $user;
                        $userTasks[$user->id]['tasks'][] = $task;
                    }
                }
            }
        }

        // 2. Notify users using summary logic
        $count = 0;
        foreach ($userTasks as $userId => $data) {
            $user = $data['user'];
            $tasksToNotify = $data['tasks'];

            if (count($tasksToNotify) === 1) {
                // Single notification
                $task = $tasksToNotify[0];
                $user->notify(new TaskReminderNotification($task));
                
                $metadata = $task->metadata ?? [];
                $metadata['last_reminder_sent_at'] = now()->toDateTimeString();
                $task->update(['metadata' => $metadata]);
            } else {
                // Summary/Batch notification
                $user->notify(new TaskSummaryNotification($tasksToNotify));
                
                foreach ($tasksToNotify as $t) {
                    $metadata = $t->metadata ?? [];
                    $metadata['last_reminder_sent_at'] = now()->toDateTimeString();
                    $t->update(['metadata' => $metadata]);
                }
            }
            $count += count($tasksToNotify);
        }

        $this->info("Processed notifications for {$count} task assignments.");
    }
}
