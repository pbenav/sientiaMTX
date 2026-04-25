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
     * Si el teamId es null, busca en todos los equipos del usuario.
     */
    public function searchTasks(?int $teamId, string $query)
    {
        Log::info("AiSearch: Buscando tareas en " . ($teamId ? "equipo $teamId" : "global") . " con query: {$query}");
        
        $queryBuilder = Task::query();
        
        if ($teamId) {
            $queryBuilder->where('team_id', $teamId);
        } else {
            $teamIds = auth()->user()->teams()->pluck('id');
            $queryBuilder->whereIn('team_id', $teamIds);
        }

        return $queryBuilder
            ->where(function($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('observations', 'LIKE', "%{$query}%");
            })
            ->select('id', 'title', 'status', 'priority', 'due_date', 'progress_percentage', 'team_id')
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'team_id' => $t->team_id,
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
    public function searchForum(?int $teamId, string $query)
    {
        Log::info("AiSearch: Buscando foro en " . ($teamId ? "equipo $teamId" : "global") . " con query: {$query}");

        $teamIds = $teamId ? [$teamId] : auth()->user()->teams()->pluck('id')->toArray();

        // Buscar hilos por título
        $threads = ForumThread::whereIn('team_id', $teamIds)
            ->where('title', 'LIKE', "%{$query}%")
            ->select('id', 'title', 'created_at', 'team_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($th) {
                return [
                    'id' => $th->id,
                    'title' => $th->title,
                    'team_id' => $th->team_id
                ];
            });

        // Buscar mensajes que contengan el término
        $messages = ForumMessage::whereHas('thread', function($q) use ($teamIds) {
                $q->whereIn('team_id', $teamIds);
            })
            ->where('content', 'LIKE', "%{$query}%")
            ->with('thread:id,title,team_id')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function($msg) {
                return [
                    'team_id' => $msg->thread->team_id,
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
