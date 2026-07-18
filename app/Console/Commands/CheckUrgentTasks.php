<?php

namespace App\Console\Commands;

use App\Models\Activity;
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

        // 0. Procesar prioridades automáticas
        $this->info('Actualizando prioridades automáticas...');
        Task::where('auto_priority', true)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->chunk(100, function ($tasks) {
                foreach ($tasks as $task) {
                    $task->updateAutoPriority();
                }
            });

        // 1. Recopilar usuarios y sus tareas/recordatorios que necesitan notificación
        $userTasks = [];

        // Obtener todos los team_ids existentes para estas tareas urgentes
        $urgentTeamIds = Task::select('team_id')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereIn('urgency', ['high', 'critical'])
            ->where('due_date', '>', now())
            ->distinct()
            ->pluck('team_id')
            ->filter()
            ->values();

        // Obtener los team_ids a los que pertenece cada usuario del sistema
        // para filtrar solo tareas de equipos donde el usuario es miembro
        $userTeamMap = []; // user_id => collection de team_ids
        $teamUsers = []; // team_id => collection de user_ids

        if ($urgentTeamIds->isNotEmpty()) {
            $teamUserRelations = \DB::table('team_user')
                ->whereIn('team_id', $urgentTeamIds)
                ->select('user_id', 'team_id')
                ->get();

            foreach ($teamUserRelations as $relation) {
                $userId = (int) $relation->user_id;
                $teamId = (int) $relation->team_id;

                if (!isset($userTeamMap[$userId])) {
                    $userTeamMap[$userId] = collect();
                }
                $userTeamMap[$userId] = $userTeamMap[$userId]->push($teamId);

                if (!isset($teamUsers[$teamId])) {
                    $teamUsers[$teamId] = collect();
                }
                $teamUsers[$teamId] = $teamUsers[$teamId]->push($userId);
            }
        }

        $tasks = Task::with(['assignedTo', 'assignedUser', 'creator'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereIn('urgency', ['high', 'critical'])
            ->where('due_date', '>', now()) // Solo tareas que aún no han vencido
            ->get()
            ->filter(function ($task) use ($teamUsers) {
                // Solo incluir tareas de equipos que tienen miembros asignados
                return isset($teamUsers[$task->team_id]) && $teamUsers[$task->team_id]->isNotEmpty();
            });

        $this->line("Tareas urgentes activas no vencidas: {$tasks->count()}");

        $allActivities = $tasks->map(fn($t) => (object)[
            'id' => 'task_' . $t->id,
            'original' => $t,
            'type' => 'task',
            'assignedTo' => $t->assignedTo,
            'assignedUser' => $t->assignedUser,
            'creator' => $t->creator,
            'team_id' => $t->team_id,
            'title' => $t->title,
            'due_date' => $t->due_date,
            'metadata' => $t->metadata ?? [],
        ]);

        // También incluir actividades tipo reminder con urgencia high/critical
        $reminderActivities = Activity::with(['assignedTo', 'assignedUser', 'creator', 'team'])
            ->where('type', 'reminder')
            ->whereJsonContains('status->value', 'pending')
            ->orWhereJsonContains('status->value', 'snoozed')
            ->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->where(function ($q) {
                $q->whereRaw("JSON_EXTRACT(metadata, '$.urgency') IN ('high', 'critical')")
                  ->orWhereRaw("JSON_EXTRACT(metadata, '$.priority') IN ('high', 'critical')");
            })
            ->get()
            ->map(fn($a) => (object)[
                'id' => 'reminder_' . $a->id,
                'original' => $a,
                'type' => 'reminder',
                'assignedTo' => $a->assignedTo,
                'assignedUser' => $a->assignedUser,
                'creator' => $a->creator,
                'team_id' => $a->team_id,
                'title' => $a->title,
                'due_date' => $a->due_date,
                'metadata' => $a->metadata ?? [],
            ]);

        $allActivities = $allActivities->merge($reminderActivities);

        foreach ($allActivities as $activity) {
            $task = $activity->original;

            // Construir lista de destinatarios:
            // 1. Usuarios asignados via tabla pivote
            $usersToNotify = $activity->assignedTo->collect();

            // 2. Usuario asignado directamente
            if ($activity->assignedUser && !$usersToNotify->contains('id', $activity->assignedUser->id ?? null)) {
                $usersToNotify->push($activity->assignedUser);
            }

            // 3. Creador (si no está ya incluido)
            if ($activity->creator && !$usersToNotify->contains('id', $activity->creator->id ?? null)) {
                $usersToNotify->push($activity->creator);
            }

            if ($usersToNotify->isEmpty()) {
                $this->line("  [skip] ID:{$activity->id} '{$activity->title}' — sin usuarios a notificar");
                continue;
            }

            // Verificar que al menos un usuario a notificar es miembro del equipo
            if (!$activity->team_id) {
                $this->line("  [skip] ID:{$activity->id} '{$activity->title}' — sin team_id");
                continue;
            }

            foreach ($usersToNotify->unique('id') as $user) {
                // CRÍTICO: Verificar que el usuario es miembro del equipo de la tarea
                $userTeams = $userTeamMap[$user->id] ?? collect();
                if (!$userTeams->contains($activity->team_id)) {
                    $this->line("  [skip] ID:{$activity->id} '{$activity->title}' — user {$user->name} no es miembro del equipo {$activity->team_id}");
                    continue;
                }

                $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
                $leadHours = (int) ($settings['notify_before_hours'] ?? 2);

                // Obtener el timezone del usuario (por defecto: config del servidor)
                $userTimezone = $user->timezone ?? config('app.timezone', 'UTC');
                
                // Convertir la hora actual y la fecha vencimiento al timezone del usuario
                $nowInUserTz = now($userTimezone);
                $dueInUserTz = $activity->due_date->copy()->setTimezone($userTimezone);
                
                // Calcular horas restantes en el timezone del usuario
                $diffHours = $nowInUserTz->diffInHours($dueInUserTz, false);

                $this->line("  ID:{$activity->id} '{$activity->title}' — due={$dueInUserTz} ({$userTimezone}) restanH={$diffHours} leadH={$leadHours} user={$user->name}");

                // Solo notificar si está dentro del margen de antelación del usuario
                $toleranceBuffer = 0.1;
                if ($diffHours >= -$toleranceBuffer && $diffHours <= ($leadHours + $toleranceBuffer)) {
                    $metadata = $activity->metadata;
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
