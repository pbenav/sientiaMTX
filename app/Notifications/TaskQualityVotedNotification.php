<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Traits\DeterminesNotificationChannels;
use NotificationChannels\WebPush\WebPushMessage;

class TaskQualityVotedNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    /** @var Task|Activity */
    public $task;

    /** @var User */
    public $voter;

    /** @var int */
    public $score;

    /**
     * @param  Task|Activity  $task  Tarea o actividad valorada
     * @param  User  $voter  Usuario que emite la valoración
     * @param  int  $score  Puntuación en estrellas (1-5)
     */
    public function __construct(Task|Activity $task, User $voter, int $score)
    {
        $this->task = $task;
        $this->voter = $voter;
        $this->score = $score;
    }

    /**
     * Formatea la notificación push web para la valoración de tarea.
     */
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("⭐ ¡Valoración excelente!")
            ->icon('/images/logo-icon.png')
            ->body("{$this->voter->name} ha puntuado tu tarea \"{$this->task->title}\" con {$this->score} estrellas.")
            ->action('Ver Tarea', 'view_task')
            ->options(['TTL' => 1000]);
    }

    /**
     * Formatea la notificación de Telegram para la valoración de tarea.
     */
    public function toTelegram($notifiable): array
    {
        $url = route('teams.activities.show', [$this->task->team_id, $this->task]);

        return [
            'text' => "⭐ *¡Valoración Excellentе!*\n\n" .
                      "{$this->voter->name} ha puntuado tu tarea *{$this->task->title}* con {$this->score} estrellas.\n\n" .
                      "[Ver Tarea]({$url})"
        ];
    }

    /**
     * Formatea el correo electrónico para la valoración de tarea.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('teams.activities.show', [$this->task->team_id, $this->task]);

        return (new MailMessage)
            ->subject("⭐ ¡Valoración excelente en: {$this->task->title}!")
            ->greeting("Hola, " . explode(' ', $notifiable->name)[0])
            ->line("{$this->voter->name} ha puntuado tu tarea \"{$this->task->title}\" con {$this->score} estrellas.")
            ->action('Ver Tarea', $url)
            ->line('¡Sigue así!');
    }

    /**
     * Convierte la notificación en un array para almacenamiento en base de datos.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'task_quality_vote',
            'title' => "¡Valoración excelente!",
            'message' => "{$this->voter->name} ha puntuado tu tarea \"{$this->task->title}\" con {$this->score} estrellas.",
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'voter_name' => $this->voter->name,
            'score' => $this->score,
            'team_id' => $this->task->team_id,
            'team_name' => $this->task->team ? $this->task->team->name : null,
            'icon' => 'star',
            'color' => 'amber'
        ];
    }
}
