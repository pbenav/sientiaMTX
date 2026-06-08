<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MorningSummaryNotification extends Notification
{
    use Queueable, \App\Traits\DeterminesNotificationChannels;

    protected $tasks;
    protected $phrase;

    /**
     * Create a new notification instance.
     */
    public function __construct($tasks, $phrase)
    {
        $this->tasks = $tasks;
        $this->phrase = $phrase;
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $taskMsg = $this->tasks->isEmpty() 
            ? 'No tienes tareas pendientes para hoy.' 
            : 'Tienes ' . $this->tasks->count() . ' tareas pendientes para hoy.';
            
        return (new WebPushMessage)
            ->title('🌅 ¡Buenos días, ' . explode(' ', $notifiable->name)[0] . '!')
            ->icon('/images/logo-icon.png')
            ->body($this->phrase . "\n" . $taskMsg)
            ->action('Ver Dashboard', 'view_dashboard')
            ->options(['TTL' => 3600]);
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $text = "🌅 *¡Buenos días, " . explode(' ', $notifiable->name)[0] . "!*\n\n";
        $text .= "_" . $this->phrase . "_\n\n";
        
        if ($this->tasks->isEmpty()) {
            $text .= "No tienes tareas prioritarias para hoy. ¡Disfruta de tu día!\n";
        } else {
            $text .= "Tus tareas para hoy:\n";

            foreach ($this->tasks->take(5) as $task) {
                $text .= "• *" . $task->title . "* (" . ($task->team->name ?? 'Personal') . ")\n";
            }

            if ($this->tasks->count() > 5) {
                $text .= "... y " . ($this->tasks->count() - 5) . " tareas más.\n";
            }
        }

        $text .= "\n🚀 ¡Que tengas un día productivo!";

        return [
            'text' => $text
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('🌅 Tu Resumen Matutino en SientiaMTX')
            ->greeting('¡Buenos días, ' . explode(' ', $notifiable->name)[0] . '!')
            ->line('"' . $this->phrase . '"');

        if ($this->tasks->isEmpty()) {
            $mail->line('No tienes tareas prioritarias programadas para hoy. ¡Aprovecha para adelantar trabajo o disfrutar de un merecido descanso!');
        } else {
            $mail->line('Aquí tienes tus tareas prioritarias para hoy:');
            $mail->line('---');

            foreach ($this->tasks->take(5) as $task) {
                $mail->line('• **' . $task->title . '** (' . ($task->team->name ?? 'Personal') . ')');
            }

            if ($this->tasks->count() > 5) {
                $mail->line('... y ' . ($this->tasks->count() - 5) . ' tareas más.');
            }
        }

        return $mail
            ->action('Ir al Dashboard', url('/dashboard'))
            ->line('¡Que tengas un día productivo y lleno de resiliencia!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $taskMsg = $this->tasks->isEmpty() 
            ? 'No tienes tareas para hoy.' 
            : 'Tienes ' . $this->tasks->count() . ' tareas para hoy.';
            
        return [
            'type' => 'morning_summary',
            'title' => 'Resumen Matutino',
            'phrase' => $this->phrase,
            'task_count' => $this->tasks->count(),
            'tasks' => $this->tasks->map(fn($t) => [
                'title' => $t->title,
                'team' => $t->team->name ?? 'Personal',
                'id' => $t->id,
                'team_id' => $t->team_id
            ])->toArray(),
            'message' => $taskMsg . ' "' . mb_substr($this->phrase, 0, 40) . '..."',
            'action_url' => route('dashboard'),
        ];
    }
}
