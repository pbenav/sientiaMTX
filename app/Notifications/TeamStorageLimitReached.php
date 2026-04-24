<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamStorageLimitReached extends Notification
{
    use Queueable;

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

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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
