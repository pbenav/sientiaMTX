<?php

namespace App\Console\Commands;

use App\Models\Activities\ReminderActivity;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TriggerReminderActivities extends Command
{
    protected $signature = 'reminders:trigger {--activity= : ID de una actividad concreta para forzar su disparo (ignora ventana temporal y anti-duplicado)}';

    protected $description = 'Dispara notificaciones de actividades tipo reminder según los canales configurados y la fecha de notificación';

    public function handle()
    {
        $this->info('Ejecutando TriggerReminderActivities...');
        $this->line('Server now (UTC): ' . now()->toDateTimeString());

        $now = now();
        $forceActivityId = $this->option('activity');

        if ($forceActivityId) {
            $this->warn("[MODO TEST] Forzando disparo solo para activity ID: {$forceActivityId}");
            $reminders = ReminderActivity::with(['assignedTo', 'assignedUser', 'creator', 'team'])
                ->where('id', $forceActivityId)
                ->get();
        } else {
            // Buscar recordatorios que tengan due_date definida Y al menos una configuración de notificación
            $reminders = ReminderActivity::with(['assignedTo', 'assignedUser', 'creator', 'team'])
                ->where(function ($query) {
                    $query->whereJsonContains('status->value', 'pending')
                        ->orWhereJsonContains('status->value', 'snoozed')
                        ->orWhereNull('status');
                })
                ->whereNotNull('due_date')
                ->where(function ($q) {
                    $q->whereNotNull('metadata->notify_before_minutes')
                      ->orWhereNotNull('metadata->notify_at_hour')
                      ->orWhere(function ($inner) {});
                })
                ->where('due_date', '<=', $now->copy()->addHours(24))
                ->where('due_date', '>=', $now->copy()->subHours(24))
                ->get();
        }

        $this->line("Recordatorios encontrados: {$reminders->count()}");

        // Construir mapa de usuarios a equipos para validación
        $teamUsers = [];
        $userTeams = [];

        if ($reminders->isNotEmpty()) {
            $reminderTeamIds = $reminders->pluck('team_id')->filter()->unique();
            if ($reminderTeamIds->isNotEmpty()) {
                $relations = \DB::table('team_user')
                    ->whereIn('team_id', $reminderTeamIds)
                    ->select('user_id', 'team_id')
                    ->get();

                foreach ($relations as $rel) {
                    $uid = (int) $rel->user_id;
                    $tid = (int) $rel->team_id;
                    if (!isset($userTeams[$uid])) $userTeams[$uid] = collect();
                    $userTeams[$uid] = $userTeams[$uid]->push($tid);
                    if (!isset($teamUsers[$tid])) $teamUsers[$tid] = collect();
                    $teamUsers[$tid] = $teamUsers[$tid]->push($uid);
                }
            }
        }

        $triggeredCount = 0;

        foreach ($reminders as $reminder) {
            if (!$reminder->team_id) {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — sin team_id");
                continue;
            }

            // Verificar si ya fue notificado (evitar duplicados en menos de 12h) — se salta en modo test
            $metadata = $reminder->metadata ?? [];
            $lastNotified = $metadata['notified_at'] ?? null;

            if (!$forceActivityId && $lastNotified && Carbon::parse($lastNotified)->addHours(12)->isFuture()) {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — ya notificado recientemente ({$lastNotified})");
                continue;
            }

            // Verificar snooze — se salta en modo test
            if (!$forceActivityId && $reminder->isSnoozed()) {
                $snoozeUntil = $reminder->isSnoozedUntil();
                if ($snoozeUntil && $snoozeUntil->isFuture()) {
                    $this->line("  [snoozed] ID:{$reminder->id} '{$reminder->title}' — hasta {$snoozeUntil}");
                    continue;
                }
            }

            // Calcular la hora exacta de notificación
            $notifyAt = $this->calculateNotifyAt($reminder, $now);

            if (!$notifyAt && !$forceActivityId) {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — sin configuración de notificación válida");
                continue;
            }

            // Verificar ventana de notificación — se salta en modo test
            if (!$forceActivityId) {
                $diffMinutes = $now->diffInMinutes($notifyAt, false);
                if ($diffMinutes < -1 || $diffMinutes > 2) {
                    $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — notificación programada para {$notifyAt->toDateTimeString()} (diferencia: {$diffMinutes} min)");
                    continue;
                }
            }

            // Construir lista de destinatarios
            $usersToNotify = $reminder->assignedTo->collect();

            if ($reminder->assignedUser && !$usersToNotify->contains('id', $reminder->assigned_user_id)) {
                $usersToNotify->push($reminder->assignedUser);
            }

            if ($reminder->creator && !$usersToNotify->contains('id', $reminder->created_by_id)) {
                $usersToNotify->push($reminder->creator);
            }

            if ($usersToNotify->isEmpty()) {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — sin usuarios a notificar");
                continue;
            }

            $channels = $reminder->getChannels();
            $this->line("  [notify_at] ID:{$reminder->id} '{$reminder->title}' — {$notifyAt->toDateTimeString()} | [channels] " . implode(', ', $channels));

            $anySent = false;

            // Un único bucle: TaskReminderNotification usa DeterminesNotificationChannels
            // cuyo via() ya decide internamente qué canales usar (mail, TelegramChannel, WebPush).
            // Los bucles separados por canal causaban triple envío a Telegram.
            foreach ($usersToNotify->unique('id') as $user) {
                if (!isset($userTeams[$user->id]) || !$userTeams[$user->id]->contains($reminder->team_id)) {
                    $this->line("    [skip] user {$user->name} no es miembro del equipo {$reminder->team_id}");
                    continue;
                }

                try {
                    $user->notify(new TaskReminderNotification($reminder));
                    $triggeredCount++;
                    $anySent = true;
                    $this->line("    [✓] Notificación enviada a {$user->name}");
                } catch (\Exception $e) {
                    Log::error("Failed to send TaskReminderNotification to user {$user->id}: " . $e->getMessage());
                }
            }

            if ($anySent) {
                $metadata['notified_at'] = now()->toDateTimeString();
                $metadata['last_channel_sent'] = $channels;
                $reminder->update(['metadata' => $metadata]);

                if ($reminder->status_value === 'pending') {
                    $reminder->update(['status' => ['value' => 'triggered']]);
                }
            } else {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — todos los canales desactivados para los destinatarios, se salta sin marcar como triggered");
            }
        }

        $this->handleRepeatingReminders();

        $this->info("Recordatorios procesados: {$triggeredCount}");
    }

    /**
     * Calcula la hora exacta de notificación basada en la configuración del recordatorio.
     *
     * Reglas:
     * - Si notify_before_minutes está definido: notificar due_date - X minutos
     * - Si notify_at_hour está definido: notificar a esa hora en la fecha de due_date
     * - Si no se define nada: notificar en la due_date exacta
     */
    protected function calculateNotifyAt(ReminderActivity $reminder, Carbon $now): ?Carbon
    {
        $dueDate = $reminder->due_date->copy();
        $metadata = $reminder->metadata ?? [];

        if (isset($metadata['notify_before_minutes']) && $metadata['notify_before_minutes'] !== null && $metadata['notify_before_minutes'] !== '') {
            // Notificar X minutos antes de la due_date
            $minutesBefore = (int) $metadata['notify_before_minutes'];
            return $dueDate->copy()->subMinutes($minutesBefore);
        }

        if (isset($metadata['notify_at_hour']) && $metadata['notify_at_hour'] !== null && $metadata['notify_at_hour'] !== '') {
            // Notificar a una hora exacta
            $notifyAtHour = $metadata['notify_at_hour'];
            // Formatos aceptados: "15:30", "15:30:00", o solo hora "15"
            if (is_string($notifyAtHour) && preg_match('/^(\d{1,2}):?(\d{2})?/?(\d{2})?$/', $notifyAtHour, $matches)) {
                $hour = (int) $matches[1];
                $minute = isset($matches[2]) ? (int) $matches[2] : 0;
                $second = isset($matches[3]) ? (int) $matches[3] : 0;
                return $dueDate->copy()->setHour($hour)->setMinute($minute)->setSecond($second);
            }
            return null;
        }

        // Sin configuración: notificar en la due_date exacta
        return $dueDate;
    }

    protected function sendNotification(User $user, ReminderActivity $reminder): void
    {
        try {
            $user->notify(new TaskReminderNotification($reminder));
        } catch (\Exception $e) {
            Log::error("Failed to send TaskReminderNotification to user {$user->id}: " . $e->getMessage());
        }
    }

    protected function sendTelegramNotification(User $user, ReminderActivity $reminder): void
    {
        try {
            $chatId = $user->telegram_chat_id;
            if (!$chatId) {
                return;
            }

            $token = config('services.telegram.bot_token');
            if (!$token) {
                Log::warning('Telegram bot token not configured.');
                return;
            }

            $url = route('teams.activities.show', [$reminder->team_id, $reminder]);
            $text = "🔔 *Recordatorio de Tarea*\n\n" .
                    "La tarea *{$reminder->title}* vence pronto.\n" .
                    "📅 Fecha: {$reminder->due_date->format('d/m/Y H:i')}\n\n" .
                    "[Ver Tarea]($url)";

            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->failed()) {
                Log::error('Telegram reminder notification failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram reminder to user {$user->id}: " . $e->getMessage());
        }
    }

    protected function sendPushNotification(User $user, ReminderActivity $reminder): void
    {
        try {
            $user->notify(new TaskReminderNotification($reminder));
        } catch (\Exception $e) {
            Log::error("Failed to send Push reminder to user {$user->id}: " . $e->getMessage());
        }
    }

    protected function handleRepeatingReminders(): void
    {
        $repeating = ReminderActivity::whereRaw("JSON_EXTRACT(metadata, '$.repeat') = 1")
            ->where('status', 'triggered')
            ->whereNotNull('due_date')
            ->get();

        foreach ($repeating as $reminder) {
            $metadata = $reminder->metadata ?? [];
            $repeatInterval = $metadata['repeat_interval'] ?? null;

            if (!$repeatInterval) {
                continue;
            }

            $lastTriggered = $metadata['notified_at'] ?? null;
            if (!$lastTriggered) {
                continue;
            }

            $lastDate = Carbon::parse($lastTriggered);
            $nextTrigger = null;

            switch ($repeatInterval) {
                case 'daily':
                    $nextTrigger = $lastDate->addDay();
                    break;
                case 'weekly':
                    $nextTrigger = $lastDate->addWeek();
                    break;
                case 'monthly':
                    $nextTrigger = $lastDate->addMonth();
                    break;
            }

            if ($nextTrigger && $nextTrigger->isPast()) {
                $metadata['notified_at'] = null;
                $reminder->update([
                    'metadata' => $metadata,
                    'status' => ['value' => 'pending'],
                ]);
                $this->line("  [repeat] ID:{$reminder->id} '{$reminder->title}' — reseteado para repetir");
            }
        }
    }
}
