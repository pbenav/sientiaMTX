<?php

namespace App\Services\Ai;

use App\Models\Task;
use App\Models\ForumThread;
use App\Models\ForumMessage;
use Illuminate\Support\Facades\Log;

class AiSearchService
{
    /**
     * Busca tareas en un equipo específico por término de búsqueda.
     */
    public function searchTasks(int $teamId, string $query)
    {
        Log::info("AiSearch: Buscando tareas en equipo {$teamId} con query: {$query}");
        
        return Task::where('team_id', $teamId)
            ->where(function($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('observations', 'LIKE', "%{$query}%");
            })
            ->select('id', 'title', 'status', 'priority', 'due_date', 'progress_percentage')
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function($t) use ($teamId) {
                return [
                    'id' => $t->id,
                    'team_id' => $teamId,
                    'title' => $t->title,
                    'status' => strtoupper($t->status),
                    'priority' => strtoupper($t->priority),
                    'due_date' => $t->due_date ? $t->due_date->format('d/m/Y') : null,
                    'progress' => $t->progress_percentage . '%'
                ];
            })
            ->toArray();
    }

    /**
     * Busca hilos y mensajes en el foro de un equipo.
     */
    public function searchForum(int $teamId, string $query)
    {
        Log::info("AiSearch: Buscando foro en equipo {$teamId} con query: {$query}");

        // Buscar hilos por título
        $threads = ForumThread::where('team_id', $teamId)
            ->where('title', 'LIKE', "%{$query}%")
            ->select('id', 'title', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($th) use ($teamId) {
                return [
                    'id' => $th->id,
                    'title' => $th->title,
                    'team_id' => $teamId
                ];
            });

        // Buscar mensajes que contengan el término
        $messages = ForumMessage::whereHas('thread', function($q) use ($teamId) {
                $q->where('team_id', $teamId);
            })
            ->where('content', 'LIKE', "%{$query}%")
            ->with('thread:id,title')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function($msg) use ($teamId) {
                return [
                    'team_id' => $teamId,
                    'thread_id' => $msg->forum_thread_id,
                    'thread_title' => $msg->thread->title,
                    'date' => $msg->created_at->format('d/m/Y H:i'),
                    'snippet' => substr(strip_tags($msg->content), 0, 200)
                ];
            });

        return [
            'threads' => $threads->toArray(),
            'message_matches' => $messages->toArray()
        ];
    }
}
