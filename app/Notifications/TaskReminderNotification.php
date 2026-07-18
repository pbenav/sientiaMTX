<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Traits\DeterminesNotificationChannels;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    protected $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task|Activity $task)
    {
        $this->task = $task;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.activities.show', [$this->task->team_id, $this->task]);
        
        $now = now();
        $dueDate = $this->task->due_date ?? $now;
        $isOverdue = $dueDate->isPast();
        $diff = $dueDate->diffForHumans($now, [
            'parts' => 2,
            'join' => true,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);

        $expiryText = $isOverdue 
            ? __('notifications.task_expired_ago', ['time' => $diff])
            : __('notifications.task_expires_in', ['time' => $diff]);

        return (new MailMessage)
            ->subject(__('notifications.task_reminder_subject', ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('notifications.task_reminder_line', [
                'title' => $this->task->title,
                'due' => $dueDate->format('d/m/Y H:i')
            ]) . ' (' . $expiryText . ')')
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
            'due_date' => $this->task->due_date?->toDateTimeString(),
            'team_id' => $this->task->team_id,
            'message' => __('notifications.task_reminder_short', ['title' => $this->task->title])
        ];
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('notifications.task_reminder_subject', ['title' => $this->task->title]))
            ->icon('/images/logo-icon.png')
            ->body(__('notifications.task_reminder_short', ['title' => $this->task->title]))
            ->action(__('notifications.view_task'), 'view_task')
            ->options(['TTL' => 1000]);
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $url = route('teams.activities.show', [$this->task->team_id, $this->task]);
        
        $dueDate = $this->task->due_date?->format('d/m/Y H:i') ?? 'Sin fecha';
        return [
            'text' => "🔔 *Recordatorio de Tarea*\n\n" . 
                      "La tarea *{$this->task->title}* vence pronto.\n" .
                      "📅 Fecha: {$dueDate}\n\n" .
                      "[Ver Tarea]($url)"
        ];
    }
}
