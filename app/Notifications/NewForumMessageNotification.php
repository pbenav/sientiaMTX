<?php

namespace App\Notifications;

use App\Models\ForumMessage;
use App\Models\ForumThread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewForumMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;
    protected $thread;

    /**
     * Create a new notification instance.
     */
    public function __construct(ForumMessage $message)
    {
        $this->message = $message;
        $this->thread = $message->thread;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];
        
        if ($notifiable instanceof \App\Models\User && $notifiable->wantsNotification('telegram')) {
            $channels[] = \App\Notifications\Channels\TelegramChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $team = $this->thread->team;
        $url = route('teams.forum.show', [$team->id, $this->thread->id]);
        
        $authorName = $this->message->user->name;
        $taskName = $this->thread->task ? $this->thread->task->title : null;
        
        $mail = (new MailMessage)
            ->subject('Nuevo comentario - ' . $this->thread->title)
            ->greeting('Hola ' . $notifiable->name . ' 👋,')
            ->line("**{$authorName}** ha dejado un nuevo comentario en el hilo de discusión **{$this->thread->title}**.");
            
        if ($taskName) {
            $mail->line("Este hilo pertenece a la tarea: **{$taskName}**");
        }
            
        return $mail->line('Dice lo siguiente:')
            ->line('"' . Str::limit($this->message->content, 100) . '"')
            ->action('Ver comentario y responder', $url)
            ->line('Gracias por usar Sientia MTX.');
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $team = $this->thread->team;
        $url = route('teams.forum.show', [$team->id, $this->thread->id]);
        $authorName = $this->message->user->name;
        $taskName = $this->thread->task ? "*Tarea*: {$this->thread->task->title}\n" : "";

        return [
            'text' => "💬 *Nuevo comentario en el foro*\n\n" .
                      "*Hilo*: {$this->thread->title}\n" .
                      $taskName .
                      "*Autor*: {$authorName}\n\n" .
                      "\"" . Str::limit($this->message->content, 100) . "\"\n\n" .
                      "[Ver comentario y responder]({$url})"
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'thread_id' => $this->thread->id,
            'message_id' => $this->message->id,
            'title' => $this->thread->title,
            'author' => $this->message->user->name,
            'team_id' => $this->thread->team_id,
            'type' => 'forum',
            'message' => Str::limit($this->message->content, 50)
        ];
    }
}
