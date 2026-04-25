<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskEventNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $type;
    protected $causer;

    /**
     * Create a new notification instance.
     * $type can be: 'blocked', 'milestone_50', 'milestone_75'
     */
    public function __construct(Task $task, string $type, $causer = null)
    {
        $this->task = $task;
        $this->type = $type;
        $this->causer = $causer ?: auth()->user();
    }

    /**
     * Get the notification's delivery channels.
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
        $subject = $this->getSubject();
        $message = $this->getMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hola, ' . explode(' ', $notifiable->name)[0])
            ->line($message)
            ->action('Ver Tarea', route('teams.tasks.show', [$this->task->team_id, $this->task->id]))
            ->line('Gracias por usar SientiaMTX.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'team_id' => $this->task->team_id,
            'team_name' => $this->task->team?->name,
            'type' => $this->type,
            'title' => $this->getSubject(),
            'message' => $this->getMessage(),
            'causer_name' => $this->causer?->name,
        ];
    }

    protected function getSubject(): string
    {
        return match($this->type) {
            'blocked' => '🚨 Tarea Bloqueada: ' . $this->task->title,
            'milestone_50' => '🎯 Hito alcanzado (50%): ' . $this->task->title,
            'milestone_75' => '🚀 Casi listo (75%): ' . $this->task->title,
            default => 'Actualización de tarea: ' . $this->task->title,
        };
    }

    protected function getMessage(): string
    {
        $userName = $this->causer?->name ?: 'Un colaborador';
        return match($this->type) {
            'blocked' => "{$userName} ha marcado la tarea como BLOQUEADA. Requiere atención inmediata para resolver el cuello de botella.",
            'milestone_50' => "¡Buenas noticias! La tarea ha alcanzado el 50% de su progreso gracias a {$userName}.",
            'milestone_75' => "La tarea está al 75%. Ya queda muy poco para completarla. ¡Buen trabajo de {$userName}!",
            default => "Hubo una actualización en la tarea.",
        };
    }
}
