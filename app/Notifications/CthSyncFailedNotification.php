<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CthSyncFailedNotification extends Notification
{
    use Queueable;

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

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
