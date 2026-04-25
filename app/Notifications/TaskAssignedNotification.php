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

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    protected $task;
    protected $assignedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, User $assignedBy)
    {
        $this->task = $task;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('tasks.notifications.assigned_alert', ['title' => $this->task->title]))
            ->icon('/images/logo-icon.png')
            ->body(__('tasks.notifications.assigned_body', ['user' => $this->assignedBy->name, 'title' => $this->task->title]))
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
            'text' => "📬 *¡NUEVA TAREA ASIGNADA!*\n\n" .
                      "*Tarea*: {$this->task->title}\n" .
                      "*Asignada por*: {$this->assignedBy->name}\n\n" .
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
            ->subject(__('tasks.notifications.assigned_alert', ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('tasks.notifications.assigned_body', ['user' => $this->assignedBy->name, 'title' => $this->task->title]))
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
            'team_name' => $this->task->team?->name,
            'assigned_by' => $this->assignedBy->name,
            'type' => 'assigned',
            'message' => __('tasks.notifications.assigned_body', ['user' => $this->assignedBy->name, 'title' => $this->task->title])
        ];
    }
}
