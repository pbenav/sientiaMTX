<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiContentTransferController extends Controller
{
    public function transferContent(Request $request, \App\Models\Team $team, \App\Models\Task $task)
    {
        if ($task->team_id !== $team->id) {
            abort(404);
        }

        if ($request->user()->cannot('view', $team) || $request->user()->cannot('view', $task)) {
            abort(403);
        }

        $payload = $this->extractPayload($request->input('content'));
        
        if ($request->target === 'description' || $request->target === 'observations' || $request->target === 'observations_append') {
            $column = ($request->target === 'description') ? 'description' : 'observations';
            $textToInject = $this->getBestTextFromPayload($payload, $column);
            
            $oldContent = $task->{$column} ?: '';
            
            // Siempre añadimos al final (append) para no perder trabajo previo
            $newContent = trim($oldContent . "\n\n" . $textToInject);

            $task->update([$column => $newContent]);
            
            $history = $task->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'ai_transfer',
                'old_values' => [$column => $oldContent],
                'new_values' => [$column => $newContent],
                'notes' => 'Transferido desde Ax.ia (' . $request->target . ')'
            ]);
            $msg = ($request->target === 'description') 
                ? "El resumen de la tarea ha sido actualizado con éxito."
                : "Se han integrado los nuevos detalles en el desarrollo de la tarea.";

            return response()->json(['success' => true, 'message' => $msg, 'history_id' => $history->id]);
        }

        if ($request->target === 'private_note' || $request->target === 'private-notes') {
            $textToInject = $this->getBestTextFromPayload($payload, 'private_note');
            
            $note = \App\Models\TaskPrivateNote::updateOrCreate(
                ['task_id' => $task->id, 'user_id' => auth()->id()],
                ['content' => $textToInject]
            );
            
            return response()->json(['success' => true, 'message' => 'Nota privada guardada y protegida correctamente.']);
        }

        if ($request->target === 'comment') {
            // Find root task for forum
            $rootTask = $task;
            while ($rootTask->parent_id && $rootTask->parent) {
                $rootTask = $rootTask->parent;
            }

            // Ensure thread exists
            $thread = $rootTask->forumThread;
            if (!$thread) {
                $thread = \App\Models\ForumThread::create([
                    'team_id' => $team->id,
                    'task_id' => $rootTask->id,
                    'title' => $request->title ?: 'Discusión: ' . $rootTask->title,
                    'user_id' => $request->user()->id,
                ]);
            }

            // Create message
            $thread->messages()->create([
                'user_id' => $request->user()->id,
                'content' => "Ax.ia: " . (is_array($payload) ? json_encode($payload) : $payload)
            ]);

            return response()->json(['success' => true, 'message' => 'Tu comentario ha sido publicado en el foro de la tarea.']);
        }

        return response()->json(['success' => false, 'message' => 'Destino no válido.']);
    }

    public function transferForumContent(Request $request, \App\Models\Team $team, \App\Models\ForumThread $thread)
    {
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        if ($request->user()->cannot('view', $team)) {
            abort(403);
        }

        $content = $this->extractPayload($request->input('content'));

        if ($request->target === 'reply' || $request->target === 'comment') {
            $thread->messages()->create([
                'user_id' => $request->user()->id,
                'content' => is_array($content) ? json_encode($content) : $content
            ]);
            return response()->json(['success' => true, 'message' => 'Respuesta publicada con éxito en el hilo de discusión.']);
        }

        if ($request->target === 'draft') {
            return response()->json(['success' => true, 'message' => 'Contenido listo para el editor.', 'content' => $content]);
        }

        // If thread has a task and target is task-related, redirect to task transfer
        if ($thread->task_id) {
            $request->merge(['task_id' => $thread->task_id]);
            return $this->transferContent($request, $team, $thread->task);
        }

        // If no task associated, treat as global transfer (create task)
        return $this->transferGlobalContent($request, $team->id);
    }

    public function transferGlobalContent(Request $request, $teamId = null)
    {
        Log::info("transferGlobalContent INICIO", [
            'request_data' => $request->all(),
            'team_id_param' => $teamId
        ]);

        // Prioridad: 1. ID en ruta, 2. ID en el cuerpo de la request, 3. Primer equipo del usuario
        $team = \App\Models\Team::find($teamId ?: $request->team_id);
        
        if (!$team) {
            $team = $request->user()->teams()->first();
        }

        if (!$team) {
            Log::warning("transferGlobalContent ERROR: No hay equipo válido.");
            return response()->json(['success' => false, 'message' => 'No tienes ningún equipo activo para recibir esta tarea.'], 422);
        }

        if ($request->user()->cannot('view', $team)) {
            Log::warning("transferGlobalContent ERROR: Permiso denegado para equipo " . $team->id);
            abort(403);
        }

        $payload = $this->extractPayload($request->input('content'));
        $user = $request->user();

        Log::info("transferGlobalContent PAYLOAD EXTRAIDO", ['payload' => $payload, 'target' => $request->target]);

        if (in_array($request->target, ['task', 'private_note', 'private-notes', 'observations', 'observations_append', 'description', 'quick-note'])) {
            $payload = $this->extractPayload($request->input('content'));
            $user = $request->user();
            
            $title = $request->title;
            $content = '';
            
            if (is_array($payload)) {
                $title = $title ?: ($payload['title'] ?? $payload['task_data']['title'] ?? null);
            }
            $content = $this->getBestTextFromPayload($payload, 'observations');

            if ($request->target === 'quick-note') {
                $note = $user->quickNotes()->create([
                    'content' => ($title ? "**$title**\n\n" : "") . $content,
                    'position_x' => 250,
                    'position_y' => 200,
                    'color' => '#fef3c7',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Se ha creado una nota rápida con el contenido de la IA.",
                    'note_id' => $note->id
                ]);
            }

            // Create a new task for this content
            $desc = 'Tarea creada desde Ax.ia.';
            $obs = $content;
            
            try {
                $task = \App\Models\Task::create([
                    'team_id' => $team->id,
                    'title' => $title ?: '📝 Tarea de Ax.ia: ' . now()->format('d/m H:i'),
                    'description' => $desc,
                    'observations' => $obs,
                    'created_by_id' => $user->id,
                    'assigned_user_id' => $user->id,
                    'visibility' => 'private',
                    'status' => 'pending'
                ]);
                $task->refresh();
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()], 500);
            }

            if ($request->target === 'private_note' || $request->target === 'private-notes') {
                \App\Models\TaskPrivateNote::create([
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'content' => $obs
                ]);
            }

            return response()->json([
                'success' => true, 
                'message' => "La tarea \"{$task->title}\" ha sido generada y configurada correctamente.",
                'task_id' => $task->id,
                'team_id' => $task->team_id
            ]);
        }

        // Find or create a "General Chat" thread for the team
        $thread = \App\Models\ForumThread::where('team_id', $team->id)
            ->whereNull('task_id')
            ->where('title', 'LIKE', '%Chat General con Ax.ia%')
            ->first();

        if (!$thread) {
            $thread = \App\Models\ForumThread::create([
                'team_id' => $team->id,
                'task_id' => null,
                'title' => $request->title ?: '🗨️ Chat General con Ax.ia',
                'user_id' => $request->user()->id,
            ]);
        }

        // Create message
        $thread->messages()->create([
            'user_id' => $request->user()->id,
            'content' => "Ax.ia: " . (is_array($content) ? json_encode($content) : $content)
        ]);

        return response()->json(['success' => true, 'message' => 'Respuesta publicada en el hilo general del equipo.']);
    }

    public function undoLastTransfer(Request $request)
    {
        $lastHistory = \App\Models\TaskHistory::where('user_id', auth()->id())
            ->where('action', 'ai_transfer')
            ->latest()
            ->first();

        if (!$lastHistory) {
            return response()->json(['success' => false, 'message' => 'No hay acciones recientes de la IA para deshacer.']);
        }

        $task = $lastHistory->task;
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'La tarea original ya no existe.']);
        }

        // Revert values
        $oldValues = $lastHistory->old_values;
        $task->update($oldValues);

        // Delete this history record so we don't undo it twice (or we could mark it as undone)
        $lastHistory->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Cambio deshecho correctamente.',
            'target' => array_keys($oldValues)[0] // e.g. 'description'
        ]);
    }

    private function getBestTextFromPayload($payload, $target = 'observations')
    {
        if (!is_array($payload)) {
            return (string) $payload;
        }

        // Intent explicit handling
        if (($payload['intent'] ?? '') === 'simple_text') {
            return $payload['content'] ?? $payload['text'] ?? '';
        }

        if (($payload['intent'] ?? '') === 'full_task') {
            $taskData = $payload['task_data'] ?? [];
            if ($target === 'description') {
                return $taskData['description'] ?? $taskData['content'] ?? $taskData['text'] ?? '';
            }
            return $taskData['observations'] ?? $taskData['description'] ?? $taskData['content'] ?? $taskData['text'] ?? '';
        }

        // General fallback search
        $keys = [$target];
        if ($target === 'observations' || $target === 'observations_append') {
            $keys[] = 'description';
        } elseif ($target === 'description') {
            $keys[] = 'observations';
        }
        
        $keys = array_merge($keys, ['content', 'text', 'message', 'body']);

        foreach ($keys as $key) {
            if (!empty($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        // Final fallback: JSON representation if nothing else
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function extractPayload($content)
    {
        if (is_array($content)) {
            return $content;
        }

        $raw = '';
        if (preg_match('/\[PAYLOAD\](.*?)\[\/PAYLOAD\]/s', $content, $matches)) {
            $raw = trim($matches[1]);
        } else {
            $raw = trim(str_replace(['[PAYLOAD]', '[/PAYLOAD]', '[INJECT]', '[/INJECT]'], '', $content));
        }

        // Limpieza de Markdown (ej. ```json ... ```) si está presente
        $raw = preg_replace('/^```\w*\n/', '', $raw);
        $raw = preg_replace('/```$/', '', trim($raw));

        // Try to decode JSON
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        
        return $raw;
    }
}
