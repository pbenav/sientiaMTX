<?php

namespace App\Notifications;

use App\Models\Kudo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class KudoReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

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
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.time-reports', $this->kudo->team_id);

        return (new MailMessage)
                    ->subject('✨ ¡Has recibido un Kudo!')
                    ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
                    ->line("{$this->sender->name} te ha enviado un Kudo por tu trabajo.")
                    ->line("**Motivo**: {$this->kudo->type}")
                    ->line("\"" . ($this->kudo->message ?? '¡Sigue así!') . "\"")
                    ->action('Ver en el Dashboard', $url)
                    ->line(__('notifications.thank_you'));
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('✨ ¡Has recibido un Kudo!')
            ->icon('/images/logo-icon.png')
            ->body("{$this->sender->name}: " . ($this->kudo->message ?? '¡Buen trabajo!'))
            ->action('Ver Dashboard', 'view_dashboard')
            ->options(['TTL' => 1000]);
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
