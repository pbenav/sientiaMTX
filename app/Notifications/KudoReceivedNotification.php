<?php

namespace App\Notifications;

use App\Models\Kudo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KudoReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $kudo;
    protected $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct(Kudo $kudo, User $sender)
    {
        $this->kudo = $kudo;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsNotification('telegram')) {
            $channels[] = \App\Notifications\Channels\TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $url = route('teams.time-reports', $this->kudo->team_id);

        return [
            'text' => "✨ *¡HAS RECIBIDO UN KUDO!*\n\n" .
                      "*De*: {$this->sender->name}\n" .
                      "*Motivo*: {$this->kudo->type}\n" .
                      "*Mensaje*: " . ($this->kudo->message ? "\"{$this->kudo->message}\"" : "Sin mensaje.") . "\n\n" .
                      "¡Sigue así! El equipo valora tu esfuerzo.\n\n" .
                      "[Ver Dashboard en Sientia]({$url})"
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kudo_id' => $this->kudo->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'type' => $this->kudo->type,
            'message' => $this->kudo->message,
            'team_id' => $this->kudo->team_id,
            'notification_type' => 'kudo_received',
            'dashboard_url' => route('teams.time-reports', $this->kudo->team_id)
        ];
    }
}
