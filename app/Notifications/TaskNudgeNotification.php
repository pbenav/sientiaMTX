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
    protected $customMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, string $type = 'collaborative', int $teamProgress = 0, ?string $customMessage = null)
    {
        $this->task = $task;
        $this->type = $type;
        $this->teamProgress = $teamProgress;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $timeText = '';
        if ($this->task->due_date) {
            $isOverdue = $this->task->due_date->isPast();
            $diff = $this->task->due_date->diffForHumans(now(), [
                'parts' => 2,
                'join' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ]);
            $timeText = $isOverdue 
                ? __('notifications.task_expired_ago', ['time' => $diff])
                : __('notifications.task_expires_in', ['time' => $diff]);
        }

        $message = __('tasks.nudges.' . $this->type, [
            'title' => $this->task->title,
            'progress' => $this->teamProgress,
            'time_text' => $timeText
        ]);

        if ($this->customMessage) {
            $message = "📢 " . __('Mensaje del Coordinador') . ":\n\"" . $this->customMessage . "\"\n\n" . $message;
        }

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
        
        $timeText = '';
        if ($this->task->due_date) {
            $isOverdue = $this->task->due_date->isPast();
            $diff = $this->task->due_date->diffForHumans(now(), [
                'parts' => 2,
                'join' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ]);
            $timeText = $isOverdue 
                ? __('notifications.task_expired_ago', ['time' => $diff])
                : __('notifications.task_expires_in', ['time' => $diff]);
        }

        $message = __('tasks.nudges.' . $this->type, [
            'title' => $this->task->title,
            'progress' => $this->teamProgress,
            'time_text' => $timeText
        ]);

        $fullText = "👉 *¡AVISO!*\n\n" . $message;

        if ($this->customMessage) {
            $fullText = "📢 *Mensaje del Coordinador:*\n_\"{$this->customMessage}\"_\n\n" . $fullText;
        }

        return [
            'text' => $fullText . "\n\n[Continuar trabajando]({$url})"
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);
        
        $timeText = '';
        if ($this->task->due_date) {
            $isOverdue = $this->task->due_date->isPast();
            $diff = $this->task->due_date->diffForHumans(now(), [
                'parts' => 2,
                'join' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ]);
            $timeText = $isOverdue 
                ? __('notifications.task_expired_ago', ['time' => $diff])
                : __('notifications.task_expires_in', ['time' => $diff]);
        }

        $message = __('tasks.nudges.' . $this->type, [
            'title' => $this->task->title,
            'progress' => $this->teamProgress,
            'time_text' => $timeText
        ]);

        $mail = (new MailMessage)
            ->subject(__('tasks.nudge_received', ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]));

        if ($this->customMessage) {
            $mail->line('📢 **' . __('Mensaje del Coordinador') . ':**')
                 ->line('"' . $this->customMessage . '"')
                 ->line('');
        }

        return $mail->line($message)
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
        $timeText = '';
        if ($this->task->due_date) {
            $isOverdue = $this->task->due_date->isPast();
            $diff = $this->task->due_date->diffForHumans(now(), [
                'parts' => 2,
                'join' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ]);
            $timeText = $isOverdue 
                ? __('notifications.task_expired_ago', ['time' => $diff])
                : __('notifications.task_expires_in', ['time' => $diff]);
        }

        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'team_id' => $this->task->team_id,
            'type' => 'nudge_' . $this->type,
            'message' => $this->customMessage ?: __('tasks.nudges.' . $this->type, [
                'title' => $this->task->title,
                'progress' => $this->teamProgress,
                'time_text' => $timeText
            ])
        ];
    }
}
