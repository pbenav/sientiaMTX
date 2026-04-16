<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class TaskCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    protected $task;
    protected $completedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, User $completedBy)
    {
        $this->task = $task;
        $this->completedBy = $completedBy;
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('tasks.notifications.completed_alert', ['title' => $this->task->title]))
            ->icon('/images/logo-icon.png')
            ->body(__('tasks.notifications.completed_body', ['user' => $this->completedBy->name, 'title' => $this->task->title]))
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
            'text' => "✅ *¡TAREA COMPLETADA!*\n\n" .
                      "*Tarea*: {$this->task->title}\n" .
                      "*Finalizada por*: {$this->completedBy->name}\n\n" .
                      "[Ver detalles en Sientia]({$url})"
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);

        return (new MailMessage)
            ->subject(__('tasks.notifications.completed_alert', ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('tasks.notifications.completed_body', ['user' => $this->completedBy->name, 'title' => $this->task->title]))
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
            'completed_by' => $this->completedBy->name,
            'type' => 'completed',
            'message' => __('tasks.notifications.completed_body', ['user' => $this->completedBy->name, 'title' => $this->task->title])
        ];
    }
}
