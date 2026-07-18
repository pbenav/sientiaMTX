<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class TeamStorageLimitReached extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    public $team;
    public $percentage;

    /**
     * Create a new notification instance.
     */
    public function __construct($team, $percentage)
    {
        $this->team = $team;
        $this->percentage = $percentage;
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('⚠️ Alerta de Almacenamiento: ' . $this->team->name)
            ->icon('/images/logo-icon.png')
            ->body('Tu equipo ha alcanzado el ' . $this->percentage . '% de su capacidad de almacenamiento.')
            ->action('Gestionar', 'storage')
            ->options(['TTL' => 1000]);
    }

    public function toTelegram($notifiable): array
    {
        return [
            'text' => "⚠️ *Alerta de Almacenamiento*\n\n" .
                      "Equipo: *{$this->team->name}*\n" .
                      "Capacidad alcanzada: *{$this->percentage}%*\n\n" .
                      "Te recomendamos realizar una limpieza de archivos obsoletos."
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Alerta de Almacenamiento: Equipo ' . $this->team->name)
            ->greeting('¡Hola, Coordinador!')
            ->line('Tu equipo "' . $this->team->name . '" ha alcanzado el ' . $this->percentage . '% de su capacidad de almacenamiento.')
            ->line('Para que los miembros puedan seguir subiendo archivos, te recomendamos realizar una limpieza de archivos obsoletos o innecesarios.')
            ->action('Gestionar Almacenamiento', route('teams.storage.index', $this->team))
            ->line('Gracias por mantener Sientia MTX optimizado y ligero.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->team->id,
            'team_name' => $this->team->name,
            'percentage' => $this->percentage,
            'message' => 'El equipo ha alcanzado el ' . $this->percentage . '% de su capacidad de almacenamiento.',
            'action_url' => route('teams.storage.index', $this->team),
            'type' => 'storage_limit'
        ];
    }
}
