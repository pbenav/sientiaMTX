<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class CthSyncFailedNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    public $action;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct($action, $reason = 'Error de conexión')
    {
        $this->action = $action;
        $this->reason = $reason;
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $actionName = $this->action === 'start' ? 'Iniciar Jornada' : 'Detener Jornada';
        return (new WebPushMessage)
            ->title("Fallo de Sincronización CTH")
            ->icon('/images/logo-icon.png')
            ->body("No se pudo sincronizar '{$actionName}': {$this->reason}")
            ->options(['TTL' => 1000]);
    }

    public function toTelegram($notifiable): array
    {
        $actionName = $this->action === 'start' ? 'Iniciar Jornada' : 'Detener Jornada';
        return [
            'text' => "⚠️ *Fallo de Sincronización CTH*\n\n" .
                      "No se pudo sincronizar *{$actionName}* con CTH.\n" .
                      "Motivo: {$this->reason}"
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $actionName = $this->action === 'start' ? 'Iniciar Jornada' : 'Detener Jornada';
        return (new MailMessage)
            ->subject("Fallo de Sincronización CTH")
            ->greeting("Hola, " . explode(' ', $notifiable->name)[0])
            ->line("No se pudo sincronizar la acción '{$actionName}' con CTH.")
            ->line("Motivo: {$this->reason}")
            ->line('Por favor, verifica tu conexión e intenta nuevamente.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actionName = $this->action === 'start' ? 'Iniciar Jornada' : 'Detener Jornada';
        return [
            'type' => 'cth_sync_failed',
            'title' => "Fallo de Sincronización CTH",
            'message' => "No se pudo sincronizar la acción '{$actionName}' con CTH. Motivo: {$this->reason}.",
            'icon' => 'warning'
        ];
    }
}
