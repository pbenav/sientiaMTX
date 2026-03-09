<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
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
        $this->info('Checking for urgent tasks...');

        // Identificamos tareas:
        // 1. No completadas ni canceladas
        // 2. Con urgencia 'high' o 'critical'
        // 3. Con fecha límite en las próximas 24 horas
        // 4. Que no hayan sido notificadas recientemente (usamos metadata para esto)
        
        $tasks = Task::with('assignedTo')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereIn('urgency', ['high', 'critical'])
            ->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addHours(24))
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            $metadata = $task->metadata ?? [];
            $lastNotified = $metadata['last_reminder_sent_at'] ?? null;

            // Evitamos enviar más de un recordatorio cada 12 horas
            if (!$lastNotified || now()->parse($lastNotified)->addHours(12)->isPast()) {
                foreach ($task->assignedTo as $user) {
                    $user->notify(new TaskReminderNotification($task));
                }

                // Actualizamos metadata
                $metadata['last_reminder_sent_at'] = now()->toDateTimeString();
                $task->update(['metadata' => $metadata]);
                
                $count++;
            }
        }

        $this->info("Reminders sent for {$count} tasks.");
    }
}
