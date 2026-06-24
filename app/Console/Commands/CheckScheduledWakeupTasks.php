<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskScheduledWakeupNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckScheduledWakeupTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-scheduled-wakeup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprueba tareas cuya fecha programada (scheduled_date) entra en la ventana de inicio y notifica a los asignados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Comprobando tareas programadas que entran en fecha de inicio...');
        $this->line('Server now (UTC): ' . now()->toDateTimeString());

        // Buscar tareas pendientes/en progreso con scheduled_date definido y en el rango cercano (próximas 2 horas o recientes)
        $tasks = Task::with(['assignedTo', 'assignedUser', 'creator'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', now()->subHours(2)) // Margen de 2 horas hacia atrás por si acaso
            ->where('scheduled_date', '<=', now()->addHours(2)) // Margen de 2 horas hacia adelante
            ->get();

        $this->line("Tareas programadas en el radar de 4 horas: {$tasks->count()}");
        $count = 0;

        foreach ($tasks as $task) {
            $metadata = $task->metadata ?? [];
            if (!empty($metadata['last_scheduled_wakeup_sent_at'])) {
                $this->line("  [skip] ID:{$task->id} '{$task->title}' — ya notificada el {$metadata['last_scheduled_wakeup_sent_at']}");
                continue;
            }

            // Construir lista de destinatarios
            $usersToNotify = $task->assignedTo->collect();

            if ($task->assignedUser && !$usersToNotify->contains('id', $task->assigned_user_id)) {
                $usersToNotify->push($task->assignedUser);
            }

            if ($task->creator && !$usersToNotify->contains('id', $task->created_by_id)) {
                $usersToNotify->push($task->creator);
            }

            if ($usersToNotify->isEmpty()) {
                $this->line("  [skip] ID:{$task->id} '{$task->title}' — sin usuarios a notificar");
                continue;
            }

            $notifiedAny = false;

            foreach ($usersToNotify->unique('id') as $user) {
                $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
                
                // ¿Tiene activada la preferencia de notificaciones para tareas programadas?
                if (!($settings['notify_scheduled_tasks'] ?? true)) {
                    $this->line("    [skip] User {$user->name} tiene desactivado notify_scheduled_tasks");
                    continue;
                }

                $leadMinutes = (int) ($settings['notify_scheduled_before_minutes'] ?? 15);
                $userTimezone = $user->timezone ?? config('app.timezone', 'UTC');
                
                $nowInUserTz = now($userTimezone);
                $scheduledInUserTz = $task->scheduled_date->copy()->setTimezone($userTimezone);
                
                // Calcular diferencia en minutos (positivo = faltan X minutos para iniciar)
                $diffMinutes = $nowInUserTz->diffInMinutes($scheduledInUserTz, false);

                $this->line("  ID:{$task->id} '{$task->title}' — sched={$scheduledInUserTz} ({$userTimezone}) restanMin={$diffMinutes} leadMin={$leadMinutes} user={$user->name}");

                // Verificamos si entra dentro del margen de antelación del usuario
                // Margen de tolerancia para incluir tanto las que ya llegaron (hasta -120 min) como las que faltan <= leadMinutes
                if ($diffMinutes >= -120 && $diffMinutes <= $leadMinutes) {
                    $user->notify(new TaskScheduledWakeupNotification($task));
                    $this->line("    [✓] Notificación de inicio enviada a {$user->name}");
                    $notifiedAny = true;
                    $count++;
                } else {
                    $this->line("    [skip] restan {$diffMinutes}m > leadMin {$leadMinutes}m — aún no entra en ventana");
                }
            }

            if ($notifiedAny) {
                $metadata['last_scheduled_wakeup_sent_at'] = now()->toDateTimeString();
                $task->update(['metadata' => $metadata]);
            }
        }

        $this->info("Procesados avisos de inicio para {$count} asignaciones.");
    }
}
