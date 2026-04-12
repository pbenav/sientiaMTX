<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class TaskNudgeNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    protected $task;
    protected $type;
    protected $teamProgress;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, string $type = 'collaborative', int $teamProgress = 0)
    {
        $this->task = $task;
        $this->type = $type;
        $this->teamProgress = $teamProgress;
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $message = __('tasks.nudges.' . $this->type, [
            'title' => $this->task->title,
            'progress' => $this->teamProgress
        ]);

        return (new WebPushMessage)
            ->title(__('tasks.nudge_received', ['title' => $this->task->title]))
            ->icon('/images/logo-icon.png')
            ->body($message)
            ->action(__('notifications.view_task'), 'view_task')
            ->options(['TTL' => 1000]);
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);
        
        $message = __('tasks.nudges.' . $this->type, [
            'title' => $this->task->title,
            'progress' => $this->teamProgress
        ]);

        return [
            'text' => "👉 *¡AVISO!*\n\n" .
                      "{$message}\n\n" .
                      "[Continuar trabajando]({$url})"
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);
        
        $message = __('tasks.nudges.' . $this->type, [
            'title' => $this->task->title,
            'progress' => $this->teamProgress
        ]);

        return (new MailMessage)
            ->subject(__('tasks.nudge_received', ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line($message)
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
            'type' => 'nudge_' . $this->type,
            'message' => __('tasks.nudges.' . $this->type, [
                'title' => $this->task->title,
                'progress' => $this->teamProgress
            ])
        ];
    }
}
