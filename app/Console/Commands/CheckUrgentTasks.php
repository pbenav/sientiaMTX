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
        \Illuminate\Support\Facades\Log::info("PABLO_REGLA_NUMERO_1: EJECUTANDO COMANDO...");
        $this->info('Checking for urgent tasks with summary logic and quotes...');

        // 1. Collect all users and their tasks that need notification
        $userTasks = [];

        // Identificamos tareas:
        // 1. No completadas ni canceladas
        // 2. Con urgencia 'high' o 'critical'
        
        $tasks = Task::with(['assignedTo', 'creator'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->get();

        foreach ($tasks as $task) {
            \Illuminate\Support\Facades\Log::info("Analizando tarea [ID: {$task->id}]: {$task->title}");

            if (!in_array($task->urgency, ['high', 'critical'])) {
                \Illuminate\Support\Facades\Log::info("  - Ignorada: Urgencia '{$task->urgency}' no es alta/crítica.");
                continue;
            }

            if ($task->due_date->isPast()) {
                \Illuminate\Support\Facades\Log::info("  - Ignorada: Ya ha vencido ({$task->due_date}).");
                continue;
            }

            if ($task->assignedTo->isEmpty()) {
                \Illuminate\Support\Facades\Log::info("  - Ignorada: No tiene usuarios asignados.");
                continue;
            }

            $usersToNotify = $task->assignedTo->collect();
            if ($task->creator && !$usersToNotify->contains('id', $task->created_by_id)) {
                $usersToNotify->push($task->creator);
                \Illuminate\Support\Facades\Log::info("  - Añadiendo al creador ({$task->creator->name}) a la lista de avisos.");
            }

            if ($usersToNotify->isEmpty()) {
                \Illuminate\Support\Facades\Log::info("  - Ignorada: No hay usuarios asignados ni creador válido.");
                continue;
            }

            foreach ($uniqueUsers = $usersToNotify->unique('id') as $user) {
                $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
                $leadHours = (int) ($settings['notify_before_hours'] ?? 24);
                $diffHours = $task->due_date->diffInHours(now());
                
                \Illuminate\Support\Facades\Log::info("  - Usuario {$user->name} (Antelación: {$leadHours}h, Faltan: {$diffHours}h)");

                // Si la tarea vence dentro del rango de antelación del usuario
                if ($diffHours <= $leadHours) {
                    $metadata = $task->metadata ?? [];
                    $lastNotified = $metadata['last_reminder_sent_at'] ?? null;

                    // Evitamos duplicar recordatorios en menos de 12 horas
                    if (!$lastNotified || now()->parse($lastNotified)->addHours(12)->isPast()) {
                        $userTasks[$user->id]['user'] = $user;
                        $userTasks[$user->id]['tasks'][] = $task;
                        \Illuminate\Support\Facades\Log::info("  - ¡NOTIFICACIÓN PROGRAMADA!");
                    } else {
                        \Illuminate\Support\Facades\Log::info("  - Ignorada: Ya notificada hace poco ({$lastNotified}).");
                    }
                } else {
                    \Illuminate\Support\Facades\Log::info("  - Ignorada: Falta demasiado tiempo para el aviso.");
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
