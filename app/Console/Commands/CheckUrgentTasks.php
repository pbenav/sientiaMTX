<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use App\Notifications\TaskSummaryNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $this->line('Server now (UTC): ' . now()->toDateTimeString());

        // 1. Recopilar usuarios y sus tareas que necesitan notificación
        $userTasks = [];

        $tasks = Task::with(['assignedTo', 'assignedUser', 'creator'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereIn('urgency', ['high', 'critical'])
            ->where('due_date', '>', now()) // Solo tareas que aún no han vencido
            ->get();

        $this->line("Tareas urgentes activas no vencidas: {$tasks->count()}");

        foreach ($tasks as $task) {
            // Construir lista de destinatarios:
            // 1. Usuarios asignados via tabla pivote (task_assignments)
            $usersToNotify = $task->assignedTo->collect();

            // 2. Usuario asignado directamente via assigned_user_id (tareas individuales)
            if ($task->assignedUser && !$usersToNotify->contains('id', $task->assigned_user_id)) {
                $usersToNotify->push($task->assignedUser);
            }

            // 3. Creador (si no está ya incluido)
            if ($task->creator && !$usersToNotify->contains('id', $task->created_by_id)) {
                $usersToNotify->push($task->creator);
            }

            if ($usersToNotify->isEmpty()) {
                $this->line("  [skip] ID:{$task->id} '{$task->title}' — sin usuarios a notificar");
                continue;
            }

            foreach ($usersToNotify->unique('id') as $user) {
                $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
                $leadHours = (int) ($settings['notify_before_hours'] ?? 2);

                // Horas restantes hasta vencimiento (positivo = futuro)
                $diffHours = now()->diffInHours($task->due_date, false);

                $this->line("  ID:{$task->id} '{$task->title}' — due={$task->due_date} (UTC) restanH={$diffHours} leadH={$leadHours} user={$user->name}");

                // Solo notificar si está dentro del margen de antelación del usuario
                // Usar un margen pequeño (0.1 horas = 6 minutos) para evitar problemas de precisión con decimales
                $toleranceBuffer = 0.1;
                if ($diffHours >= -$toleranceBuffer && $diffHours <= ($leadHours + $toleranceBuffer)) {
                    $metadata = $task->metadata ?? [];
                    $lastNotified = $metadata['last_reminder_sent_at'] ?? null;

                    // Evitar duplicados en menos de 12 horas
                    $alreadyNotified = $lastNotified && Carbon::parse($lastNotified)->addHours(12)->isFuture();

                    if ($alreadyNotified) {
                        $this->line("    [skip] ya notificado recientemente ({$lastNotified})");
                        continue;
                    }

                    $userTasks[$user->id]['user'] = $user;
                    $userTasks[$user->id]['tasks'][] = $task;
                    $this->line("    [✓] Añadido para notificación");
                } else {
                    $this->line("    [skip] restan {$diffHours}h > leadH {$leadHours}h — fuera del margen");
                }
            }
        }

        // 2. Notificar con lógica de resumen
        $count = 0;
        foreach ($userTasks as $userId => $data) {
            $user = $data['user'];
            $tasksToNotify = $data['tasks'];

            if (count($tasksToNotify) === 1) {
                $task = $tasksToNotify[0];
                $user->notify(new TaskReminderNotification($task));

                $metadata = $task->metadata ?? [];
                $metadata['last_reminder_sent_at'] = now()->toDateTimeString();
                $task->update(['metadata' => $metadata]);
            } else {
                $user->notify(new TaskSummaryNotification($tasksToNotify));

                foreach ($tasksToNotify as $t) {
                    $metadata = $t->metadata ?? [];
                    $metadata['last_reminder_sent_at'] = now()->toDateTimeString();
                    $t->update(['metadata' => $metadata]);
                }
            }
            $count += count($tasksToNotify);
        }

        $this->info("Procesadas notificaciones para {$count} asignaciones.");
    }
}
