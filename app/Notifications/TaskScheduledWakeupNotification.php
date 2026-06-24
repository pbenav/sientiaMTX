<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class TaskScheduledWakeupNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    protected $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('¡Tarea lista para iniciar: :title!', ['title' => $this->task->title]))
            ->icon('/images/logo-icon.png')
            ->body(__('La tarea programada ":title" ya está en su fecha de inicio y lista para ponerse en funcionamiento.', ['title' => $this->task->title]))
            ->action(__('notifications.view_task'), 'view_task')
            ->options(['TTL' => 1000]);
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);

        return [
            'text' => "⏳ *¡TAREA LISTA PARA INICIAR!*\n\n" .
                      "*Tarea*: {$this->task->title}\n" .
                      "*Fecha Programada*: {$this->task->scheduled_date->format('d/m/Y H:i')}\n\n" .
                      "La tarea ya ha entrado en su ventana de inicio y está lista para ser ejecutada.\n\n" .
                      "[Ver detalles en Sientia Open Source Lab]({$url})"
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);

        return (new MailMessage)
            ->subject(__('¡Tarea lista para iniciar: :title!', ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('La tarea programada ":title" ya ha alcanzado su fecha de inicio y puede ser puesta en funcionamiento.', ['title' => $this->task->title]))
            ->action(__('notifications.view_task'), $url)
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
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'team_id' => $this->task->team_id,
            'type' => 'scheduled_wakeup',
            'message' => __('La tarea programada ":title" ya está en su fecha de inicio y lista para ponerse en funcionamiento.', ['title' => $this->task->title])
        ];
    }
}
