<?php

namespace App\Http\Controllers;

use App\Contracts\AiAssistantInterface;
use Illuminate\Http\Request;

use App\Models\AiChatMessage;

class AiChatController extends Controller
{
    public function getAvailableModels(Request $request, AiAssistantInterface $aiService)
    {
        $aiService->forUser($request->user(), $request->team_id);
        
        // Si viene una clave en la request, la usamos para la consulta temporal
        if ($request->api_key) {
            // Necesitamos acceder a la propiedad protegida o añadir un setter
            // Por ahora, si el servicio lo soporta, la inyectamos.
            if (method_exists($aiService, 'setTemporaryKey')) {
                $aiService->setTemporaryKey($request->api_key);
            }
        }

        $models = $aiService->listAvailableModels();

        return response()->json([
            'models' => $models,
            'current_model' => $aiService->forUser($request->user(), $request->team_id)->getTargetModel()
        ]);
    }

    public function getHistory(Request $request)
    {
        $messages = AiChatMessage::where('user_id', $request->user()->id)
            ->where('team_id', $request->team_id)
            ->latest()
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
            'current_model' => app(AiAssistantInterface::class)->forUser($request->user(), $request->team_id)->getTargetModel()
        ]);
    }

    public function ask(Request $request, AiAssistantInterface $aiAssistant)
    {
        $request->validate([
            'prompt' => 'nullable|string|max:100000',
            'team_id' => 'nullable|integer|exists:teams,id',
            'task_id' => 'nullable|integer|exists:tasks,id',
            'attachment_id' => 'nullable|integer|exists:task_attachments,id',
            'forum_thread_id' => 'nullable|integer|exists:forum_threads,id',
            'forum_message_id' => 'nullable|integer|exists:forum_messages,id',
            'file' => 'nullable|file|max:20480' // 20MB limit
        ]);

        set_time_limit(300); // 5 minutos para procesar archivos grandes
        ini_set('memory_limit', '1024M'); // Doble de memoria para Base64 de PDF/Audio
        $user = $request->user();

        if ($request->team_id) {
            $team = \App\Models\Team::find($request->team_id);
            if (!$team || $user->cannot('view', $team)) {
                return response()->json(['message' => 'No tienes acceso a este equipo.'], 403);
            }
        }

        if ($request->task_id) {
            $task = \App\Models\Task::find($request->task_id);
            if (!$task || $user->cannot('view', $task)) {
                return response()->json(['message' => 'No tienes acceso a esta tarea.'], 403);
            }
        }

        if ($request->forum_thread_id) {
            $thread = \App\Models\ForumThread::find($request->forum_thread_id);
            if (!$thread || $user->cannot('view', $thread->team)) { // Simple check for now
                return response()->json(['message' => 'No tienes acceso a este hilo.'], 403);
            }
        }
        $prompt = $request->prompt;

        if ($request->hasFile('file')) {
            \Illuminate\Support\Facades\Log::info("Subiendo archivo para IA: " . $request->file('file')->getClientOriginalName());
        }
        $prompt = $request->prompt;

        if (!$prompt && !$request->hasFile('file')) {
            return response()->json(['message' => '¿En qué puedo ayudarte?'], 422);
        }

        if (!$prompt && $request->hasFile('file')) {
            $prompt = 'Hola Ax.ia, he adjuntado un archivo. Por favor, analízalo y dime lo más relevante o responde a lo que contenga si es una instrucción.';
        }

        // 1. Persist User Message
        $contentToStore = $prompt;
        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $fileName = $originalName;
            
            // Pass to assistant IMMEDIATELY to capture content before store() moves it
            $aiAssistant->withFile($file);
            
            // Save file
            $path = $file->store('ai_attachments', 'public');
            $filePath = $path;
            
            $contentToStore = "📁 [Archivo: " . $fileName . "]\n\n" . $prompt;
        }

        AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'role' => 'user',
            'content' => $contentToStore,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);

        $aiAssistant->forUser($user, $request->team_id);

        // (withFile already called above if present)

        if ($request->task_id) {
            $task = \App\Models\Task::find($request->task_id);
            if ($task) {
                $aiAssistant->withTaskContext($task);
            }
        } else {
            // If no specific task context, provide a general list of pending tasks for context
            $pendingTasks = $user->assignedTasks()
                ->where('status', '!=', 'completed')
                ->where(function($q) use ($request) {
                    if ($request->team_id) $q->where('tasks.team_id', $request->team_id);
                })
                ->limit(15)
                ->get();
            
            if ($pendingTasks->count() > 0) {
                $aiAssistant->withTasksContext($pendingTasks);
            }
        }

        if ($request->attachment_id) {
            $attachment = \App\Models\TaskAttachment::find($request->attachment_id);
            if ($attachment) {
                // Security Audit Fix: Check access to the attachment context
                $isManager = $request->team_id ? $request->user()->isManager(\App\Models\Team::find($request->team_id)) : false;
                $canAccess = \App\Models\Task::where('id', $attachment->task_id)->visibleTo($user, $isManager)->exists();
                
                if (!$canAccess) {
                    return response()->json(['message' => 'No tienes permiso para analizar este archivo.'], 403);
                }

                $aiAssistant->withAttachmentContext($attachment);
                
                // If no new file was uploaded, we link the existing attachment to the message
                if (!$filePath) {
                    $filePath = $attachment->file_path;
                    $fileName = $attachment->file_name;
                    $fileType = $attachment->mime_type;
                    
                    // Update the message we just created
                    $userMessage = AiChatMessage::where('user_id', $user->id)
                        ->where('role', 'user')
                        ->latest()
                        ->first();
                    
                    if ($userMessage) {
                        $userMessage->update([
                            'file_path' => $filePath,
                            'file_name' => $fileName,
                            'file_type' => $fileType,
                        ]);
                    }
                }
            }
        }

        if ($request->forum_thread_id) {
            $thread = \App\Models\ForumThread::find($request->forum_thread_id);
            if ($thread) {
                // Security Audit Fix: Check access to the forum context (especially if linked to a private task)
                if ($user->cannot('view', $thread->team)) {
                    return response()->json(['message' => 'No tienes acceso a este equipo.'], 403);
                }
                
                if ($thread->task_id) {
                    // Si la tarea es privada y el usuario no tiene acceso, el controlador del foro 
                    // permite a los coordinadores ver el hilo pero no la tarea. 
                    // Permitimos que la IA procese el hilo si el usuario es manager o puede ver la tarea.
                    $isManager = $thread->team->isManager($user);
                    if (!$isManager && $user->cannot('view', $thread->task)) {
                        return response()->json(['message' => 'No tienes permiso para acceder al contenido de esta tarea.'], 403);
                    }
                }

                $message = $request->forum_message_id ? \App\Models\ForumMessage::find($request->forum_message_id) : null;
                $aiAssistant->withForumContext($thread, $message);
            }
        }
        
        $response = $aiAssistant->generateText($prompt);
        
        // Human Recharge Logic
        if (str_contains($response, '[RECHARGE]')) {
            $user->increment('energy_level', 20);
            if ($user->energy_level > 100) {
                $user->update(['energy_level' => 100]);
            }
            $response = str_replace('[RECHARGE]', '', $response);
        }

        // 2. Persist AI Message
        AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'role' => 'ai',
            'content' => $response,
        ]);

        return response()->json([
            'message' => $response,
            'current_model' => $aiAssistant->getTargetModel()
        ]);
    }

    public function clearHistory(Request $request)
    {
        AiChatMessage::where('user_id', $request->user()->id)
            ->when($request->team_id, function($q) use ($request) {
                return $q->where('team_id', $request->team_id);
            })
            ->delete();

        return response()->json(['success' => true]);
    }

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
            
            $textToInject = '';
            if (is_array($payload)) {
                if (($payload['intent'] ?? '') === 'simple_text') {
                    $textToInject = $payload['content'] ?? '';
                } elseif (($payload['intent'] ?? '') === 'full_task') {
                    $taskData = $payload['task_data'] ?? [];
                    $textToInject = $taskData[$column] ?? $taskData['description'] ?? $taskData['observations'] ?? '';
                } else {
                    $textToInject = $payload[$column] ?? $payload['description'] ?? $payload['observations'] ?? '';
                }
            } else {
                $textToInject = $payload;
            }
            
            $oldContent = $task->{$column} ?: '';
            
            // Si es descripción, ¿sobrescribimos o añadimos? 
            // El usuario dijo "sobrescribir" en el Swal del componente.
            if ($request->target === 'description') {
                $newContent = trim($textToInject);
            } else {
                $newContent = trim($oldContent . "\n\n" . $textToInject);
            }

            $task->update([$column => $newContent]);
            
            $history = $task->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'ai_transfer',
                'old_values' => [$column => $oldContent],
                'new_values' => [$column => $newContent],
                'notes' => 'Transferido desde Ax.ia (' . $request->target . ')'
            ]);
            return response()->json(['success' => true, 'message' => "Contenido inyectado en {$column} correctamente.", 'history_id' => $history->id]);
        }

        if ($request->target === 'private_note' || $request->target === 'private-notes') {
            $textToInject = '';
            if (is_array($payload)) {
                if (($payload['intent'] ?? '') === 'simple_text') {
                    $textToInject = $payload['content'] ?? '';
                } elseif (($payload['intent'] ?? '') === 'full_task') {
                    $textToInject = $payload['task_data']['observations'] ?? json_encode($payload['task_data']);
                } else {
                    $textToInject = $payload['description'] ?? $payload['observations'] ?? json_encode($payload);
                }
            } else {
                $textToInject = $payload;
            }
            $note = \App\Models\TaskPrivateNote::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'content' => $textToInject
            ]);
            
            return response()->json(['success' => true, 'message' => 'Nota interna/privada creada correctamente.']);
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
                'content' => "Ax.ia: " . $content
            ]);

            return response()->json(['success' => true, 'message' => 'Respuesta publicada como comentario.']);
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
                'content' => $content
            ]);
            return response()->json(['success' => true, 'message' => 'Respuesta publicada en el hilo.']);
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
        return $this->transferGlobalContent($request, $team);
    }

    public function transferGlobalContent(Request $request, $teamId = null)
    {
        \Illuminate\Support\Facades\Log::info("transferGlobalContent INICIO", [
            'request_data' => $request->all(),
            'team_id_param' => $teamId
        ]);

        // Prioridad: 1. ID en ruta, 2. ID en el cuerpo de la request, 3. Primer equipo del usuario
        $team = \App\Models\Team::find($teamId ?: $request->team_id);
        
        if (!$team) {
            $team = $request->user()->teams()->first();
        }

        if (!$team) {
            \Illuminate\Support\Facades\Log::warning("transferGlobalContent ERROR: No hay equipo válido.");
            return response()->json(['success' => false, 'message' => 'No tienes ningún equipo activo para recibir esta tarea.'], 422);
        }

        if ($request->user()->cannot('view', $team)) {
            \Illuminate\Support\Facades\Log::warning("transferGlobalContent ERROR: Permiso denegado para equipo " . $team->id);
            abort(403);
        }

        $payload = $this->extractPayload($request->input('content'));
        $user = $request->user();

        \Illuminate\Support\Facades\Log::info("transferGlobalContent PAYLOAD EXTRAIDO", ['payload' => $payload, 'target' => $request->target]);

        if (in_array($request->target, ['task', 'private_note', 'private-notes', 'observations', 'observations_append', 'description'])) {
            // Create a new task for this content
            $title = $request->title;
            $desc = 'Tarea creada desde Ax.ia.';
            $obs = '';
            
            if (is_array($payload)) {
                if (($payload['intent'] ?? '') === 'simple_text') {
                    $title = $title ?: 'Nota: ' . now()->format('d/m H:i');
                    $obs = $payload['content'] ?? '';
                } elseif (($payload['intent'] ?? '') === 'full_task') {
                    $taskData = $payload['task_data'] ?? [];
                    $title = $title ?: ($taskData['title'] ?? null);
                    $desc = $taskData['description'] ?? $desc;
                    $obs = $taskData['observations'] ?? '';
                } else {
                    $title = $title ?: ($payload['title'] ?? null);
                    $desc = $payload['description'] ?? $desc;
                    $obs = $payload['observations'] ?? '';
                }
            } else {
                $obs = $payload;
            }

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
                $task->refresh(); // ensure all fields are set
                \Illuminate\Support\Facades\Log::info("transferGlobalContent TAREA CREADA", ['task_id' => $task->id]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("transferGlobalContent ERROR CREADO TAREA", ['e' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()], 500);
            }

            if ($request->target === 'private_note' || $request->target === 'private-notes') {
                $noteContent = '';
                if (is_array($payload)) {
                     if (($payload['intent'] ?? '') === 'simple_text') {
                         $noteContent = $payload['content'] ?? '';
                     } elseif (($payload['intent'] ?? '') === 'full_task') {
                         $noteContent = $payload['task_data']['observations'] ?? 'Nota de tarea';
                     } else {
                         $noteContent = $payload['observations'] ?? 'Nota de tarea';
                     }
                } else {
                     $noteContent = $payload;
                }

                \App\Models\TaskPrivateNote::create([
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'content' => $noteContent
                ]);
            }

            return response()->json([
                'success' => true, 
                'message' => "Tarea \"{$task->title}\" creada con éxito.",
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
            'content' => "Ax.ia: " . $content
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

    private function extractPayload($content)
    {
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
