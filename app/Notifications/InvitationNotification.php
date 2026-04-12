<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TeamInvitation; // Assuming TeamInvitation model exists

use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class InvitationNotification extends Notification
{
    use Queueable, DeterminesNotificationChannels;

    protected $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(TeamInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject(__('teams.invitation.subject', ['team' => $this->invitation->team->name]))
                    ->greeting(__('teams.invitation.greeting'))
                    ->line(__('teams.invitation.line1', ['team' => $this->invitation->team->name]))
                    ->action(__('teams.invitation.action'), route('invitations.accept', ['token' => $this->invitation->token]))
                    ->line(__('teams.invitation.line2'))
                    ->line(__('notifications.thank_you'));
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $url = route('invitations.accept', ['token' => $this->invitation->token]);

        return [
            'text' => "📩 *¡INVITACIÓN RECIBIDA!*\n\n" .
                      "Has sido invitado a unirte al equipo *{$this->invitation->team->name}*.\n\n" .
                      "[Aceptar Invitación]($url)"
        ];
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('teams.invitation.subject', ['team' => $this->invitation->team->name]))
            ->icon('/images/logo-icon.png')
            ->body(__('teams.invitation.line1', ['team' => $this->invitation->team->name]))
            ->action(__('teams.invitation.action'), 'accept_invitation')
            ->options(['TTL' => 1000]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'team_name' => $this->invitation->team->name,
            'token' => $this->invitation->token,
            'type' => 'invitation',
            'message' => __('Has sido invitado al equipo :team', ['team' => $this->invitation->team->name])
        ];
    }
}
