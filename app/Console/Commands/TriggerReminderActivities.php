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
    protected $signature = 'reminders:trigger';

    protected $description = 'Dispara notificaciones de actividades tipo reminder según los canales configurados y la fecha de vencimiento';

    public function handle()
    {
        $this->info('Ejecutando TriggerReminderActivities...');
        $this->line('Server now (UTC): ' . now()->toDateTimeString());

        // 1. Buscar recordatorios pendientes que deben dispararse
        // Ventana: desde vencidos hace <= 5 min hasta vencen en las próximas 4h
        // Esto permite recordatorios configurados con 1h, 2h, 4h, etc. de anticipación
        $now = now();
        $reminders = ReminderActivity::with(['assignedTo', 'assignedUser', 'creator', 'team'])
            ->where(function ($query) {
                $query->whereJsonContains('status->value', 'pending')
                    ->orWhereJsonContains('status->value', 'snoozed');
            })
            ->orWhereNull('status')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $now->copy()->addHours(4))
            ->where('due_date', '>=', $now->copy()->subMinutes(5))
            ->get();

        // Construir mapa de usuarios a equipos para validación
        $teamUsers = []; // team_id => collection de user_ids
        $userTeams = []; // user_id => collection de team_ids

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
            // CRÍTICO: Verificar que el recordatorio pertenece a un equipo válido
            if (!$reminder->team_id) {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — sin team_id");
                continue;
            }

            // Verificar si ya fue notificado (evitar duplicados en menos de 12h)
            $metadata = $reminder->metadata ?? [];
            $lastNotified = $metadata['notified_at'] ?? null;

            if ($lastNotified && Carbon::parse($lastNotified)->addHours(12)->isFuture()) {
                $this->line("  [skip] ID:{$reminder->id} '{$reminder->title}' — ya notificado recientemente ({$lastNotified})");
                continue;
            }

            // Verificar snooze
            if ($reminder->isSnoozed()) {
                $snoozeUntil = $reminder->isSnoozedUntil();
                if ($snoozeUntil && $snoozeUntil->isFuture()) {
                    $this->line("  [snoozed] ID:{$reminder->id} '{$reminder->title}' — hasta {$snoozeUntil}");
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

            // Obtener canales configurados para este recordatorio
            $channels = $reminder->getChannels();
            $this->line("  [channels] ID:{$reminder->id} '{$reminder->title}' — " . implode(', ', $channels));

            // Para cada canal configurado, enviar a los usuarios que tengan ese canal activado
            // Y que sean miembros del equipo del recordatorio
            if (in_array('email', $channels, true) || in_array('mail', $channels, true)) {
                foreach ($usersToNotify->unique('id') as $user) {
                    // Verificar que el usuario es miembro del equipo
                    if (!isset($userTeams[$user->id]) || !$userTeams[$user->id]->contains($reminder->team_id)) {
                        $this->line("    [skip] user {$user->name} no es miembro del equipo {$reminder->team_id}");
                        continue;
                    }
                    if ($user->wantsNotification('mail')) {
                        $this->sendNotification($user, $reminder);
                        $triggeredCount++;
                        $this->line("    [✓] Email enviado a {$user->name}");
                    }
                }
            }

            if (in_array('telegram', $channels, true)) {
                foreach ($usersToNotify->unique('id') as $user) {
                    if (!isset($userTeams[$user->id]) || !$userTeams[$user->id]->contains($reminder->team_id)) {
                        $this->line("    [skip] user {$user->name} no es miembro del equipo {$reminder->team_id}");
                        continue;
                    }
                    if ($user->wantsNotification('telegram') && !empty($user->telegram_chat_id)) {
                        $this->sendTelegramNotification($user, $reminder);
                        $triggeredCount++;
                        $this->line("    [✓] Telegram enviado a {$user->name}");
                    }
                }
            }

            if (in_array('push', $channels, true) || in_array('web_push', $channels, true)) {
                foreach ($usersToNotify->unique('id') as $user) {
                    if (!isset($userTeams[$user->id]) || !$userTeams[$user->id]->contains($reminder->team_id)) {
                        $this->line("    [skip] user {$user->name} no es miembro del equipo {$reminder->team_id}");
                        continue;
                    }
                    if ($user->wantsNotification('web_push')) {
                        $this->sendPushNotification($user, $reminder);
                        $triggeredCount++;
                        $this->line("    [✓] Push enviado a {$user->name}");
                    }
                }
            }

            // Marcar como notificado
            $metadata['notified_at'] = now()->toDateTimeString();
            $metadata['last_channel_sent'] = $channels;
            $reminder->update(['metadata' => $metadata]);

            // Actualizar status a triggered si está pendiente
            if ($reminder->status_value === 'pending') {
                $reminder->update(['status' => ['value' => 'triggered']]);
            }
        }

        // 2. Manejar recordatorios repetitivos
        $this->handleRepeatingReminders();

        $this->info("Recordatorios procesados: {$triggeredCount}");
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
            // Usar TaskReminderNotification que ya implementa toWebPush
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
                // Resetear para que vuelva a dispararse en el próximo cron
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
