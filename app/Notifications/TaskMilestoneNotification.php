<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskMilestoneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;
    protected $progress;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, int $progress)
    {
        $this->task = $task;
        $this->progress = $progress;
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
        $url = route('teams.tasks.show', [$this->task->team_id, $this->task]);
        $key = 'p' . $this->progress;

        return (new MailMessage)
            ->subject(__('tasks.milestones.' . $key, ['title' => $this->task->title]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('tasks.milestones.' . $key, ['title' => $this->task->title]))
            ->line(__('tasks.roadmap_progress') . ': ' . $this->progress . '%')
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
            'progress' => $this->progress,
            'type' => 'milestone',
            'message' => __('tasks.milestones.p' . $this->progress, ['title' => $this->task->title])
        ];
    }
}
