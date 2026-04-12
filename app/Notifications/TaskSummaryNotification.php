<?php

namespace App\Notifications;

use App\Services\QuoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Traits\DeterminesNotificationChannels;

class TaskSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    protected $tasks;
    protected $quoteData;

    /**
     * Create a new notification instance.
     */
    public function __construct($tasks)
    {
        $this->tasks = $tasks;
        $this->quoteData = app(QuoteService::class)->getWelcomeMessage();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->tasks);
        $mail = (new MailMessage)
            ->subject(__('notifications.task_summary_subject', ['count' => $count]))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name]))
            ->line(__('notifications.task_summary_intro', ['count' => $count]));

        foreach ($this->tasks as $task) {
            $mail->line("- **{$task->title}** (Vence: {$task->due_date->format('d/m/Y H:i')})");
        }

        if ($this->quoteData['quote']) {
            $mail->line("\n_\"" . $this->quoteData['quote']->text . "\"_ — " . ($this->quoteData['quote']->author ?? 'Anon'));
        }

        return $mail->action(__('Ver Dashboard'), route('dashboard'));
    }

    /**
     * Get the Web Push representation.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('Tareas Urgentes (:count)', ['count' => count($this->tasks)]))
            ->body(__('Tienes varias entregas pendientes. ¡Tú puedes con ello!'))
            ->icon('/images/logo-icon.png')
            ->action(__('Ver todas'), 'view_dashboard');
    }

    /**
     * Get the Telegram representation.
     */
    public function toTelegram(object $notifiable): array
    {
        $text = "🚀 *Resumen de Tareas Urgentes*\n\n";
        
        foreach ($this->tasks as $task) {
            $text .= "• *{$task->title}* ({$task->due_date->format('H:i')})\n";
        }

        if ($this->quoteData['quote']) {
            $text .= "\n_\"" . $this->quoteData['quote']->text . "\"_";
        }

        return ['text' => $text];
    }

    /**
     * Get the array representation.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_count' => count($this->tasks),
            'message' => __('Tienes :count tareas urgentes hoy.', ['count' => count($this->tasks)]),
            'tasks' => collect($this->tasks)->map(fn($t) => ['id' => $t->id, 'title' => $t->title])->toArray()
        ];
    }
}
