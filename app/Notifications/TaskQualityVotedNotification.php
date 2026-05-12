<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskQualityVotedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $task;
    public $voter;
    public $score;

    public function __construct(Task $task, User $voter, int $score)
    {
        $this->task = $task;
        $this->voter = $voter;
        $this->score = $score;
    }

    public function via($notifiable)
    {
        // Store in database primarily for standard alerting
        return ['database'];
    }

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
