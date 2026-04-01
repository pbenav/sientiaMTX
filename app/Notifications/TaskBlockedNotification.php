<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskBlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;
    protected $reportedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, User $reportedBy)
    {
        $this->task = $task;
        $this->reportedBy = $reportedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable instanceof User && $notifiable->wantsNotification('telegram')) {
            $channels[] = \App\Notifications\Channels\TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);

        return [
            'text' => "🚫 *¡TAREA BLOQUEADA!*\n\n" .
                      "*Tarea*: {$this->task->title}\n" .
                      "*Reportado por*: {$this->reportedBy->name}\n\n" .
                      "Se requiere atención para desbloquear esta tarea.\n\n" .
                      "[Abrir tarea en Sientia]({$url})"
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);

        return (new MailMessage)
            ->subject(__('tasks.notifications.blocked_alert'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('tasks.notifications.blocked_alert', ['title' => $this->task->title]))
            ->line(__('tasks.personal_instance_notice') . ': ' . $this->reportedBy->name)
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
            'reported_by' => $this->reportedBy->name,
            'type' => 'blocked',
            'message' => __('tasks.notifications.blocked_alert', ['title' => $this->task->title])
        ];
    }
}
