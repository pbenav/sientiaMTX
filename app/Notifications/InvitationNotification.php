<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TeamInvitation; // Assuming TeamInvitation model exists

class InvitationNotification extends Notification
{
    use Queueable;

    protected $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(TeamInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
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
                    ->action(__('teams.invitation.action'), route('register', ['invitation' => $this->invitation->token, 'email' => $this->invitation->email]))
                    ->line(__('teams.invitation.line2'))
                    ->line(__('notifications.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
