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
            'models' => $models
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
            'messages' => $messages
        ]);
    }

    public function ask(Request $request, AiAssistantInterface $aiAssistant)
    {
        $request->validate([
            'prompt' => 'nullable|string|max:2000',
            'team_id' => 'nullable|integer|exists:teams,id',
            'task_id' => 'nullable|integer|exists:tasks,id',
            'attachment_id' => 'nullable|integer|exists:task_attachments,id',
            'forum_thread_id' => 'nullable|integer|exists:forum_threads,id',
            'forum_message_id' => 'nullable|integer|exists:forum_messages,id',
            'file' => 'nullable|file|max:20480' // 20MB limit
        ]);

        ini_set('memory_limit', '512M'); // Asegurar memoria para Base64
        $user = $request->user();
        $prompt = $request->prompt;

        if ($request->hasFile('file')) {
            \Illuminate\Support\Facades\Log::info("Subiendo archivo para IA: " . $request->file('file')->getClientOriginalName());
        }
        $prompt = $request->prompt;

        if (!$prompt && !$request->hasFile('file')) {
            return response()->json(['message' => '¿En qué puedo ayudarte?'], 422);
        }

        if (!$prompt && $request->hasFile('file')) {
            $prompt = 'Analiza este archivo. SI CONTIENE UNA PREGUNTA O INSTRUCCIÓN, RESPÓNDELA DIRECTAMENTE CON PRIORIDAD. Si solo es información, transcríbela y resúmela o propón acciones.';
        }

        // 1. Persist User Message
        $contentToStore = $prompt;
        if ($request->hasFile('file')) {
            $contentToStore = "📁 [Archivo: " . $request->file('file')->getClientOriginalName() . "]\n\n" . $prompt;
        }

        AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'role' => 'user',
            'content' => $contentToStore,
        ]);

        $aiAssistant->forUser($user, $request->team_id);

        if ($request->hasFile('file')) {
            $aiAssistant->withFile($request->file('file'));
        }

        if ($request->task_id) {
            $task = \App\Models\Task::find($request->task_id);
            if ($task) {
                $aiAssistant->withTaskContext($task);
            }
        }

        if ($request->attachment_id) {
            $attachment = \App\Models\TaskAttachment::find($request->attachment_id);
            if ($attachment) {
                $aiAssistant->withAttachmentContext($attachment);
            }
        }

        if ($request->forum_thread_id) {
            $thread = \App\Models\ForumThread::find($request->forum_thread_id);
            if ($thread) {
                $message = $request->forum_message_id ? \App\Models\ForumMessage::find($request->forum_message_id) : null;
                $aiAssistant->withForumContext($thread, $message);
            }
        }
        
        $response = $aiAssistant->generateText($prompt);

        // 2. Persist AI Message
        AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'role' => 'ai',
            'content' => $response,
        ]);

        return response()->json([
            'message' => $response
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
        $request->validate([
            'content' => 'required|string',
            'target' => 'required|string|in:description,observations,comment',
            'title' => 'nullable|string|max:255'
        ]);

        $content = $this->extractPayload($request->input('content'));

        if ($request->target === 'description') {
            $task->update(['description' => $content]);
            return response()->json(['success' => true, 'message' => 'Descripción actualizada correctamente.']);
        }

        if ($request->target === 'observations') {
            $task->update(['observations' => $content]);
            return response()->json(['success' => true, 'message' => 'Observaciones actualizadas correctamente.']);
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
        $request->validate([
            'content' => 'required|string',
            'target' => 'required|string|in:reply,comment,description'
        ]);

        $content = $this->extractPayload($request->input('content'));

        if ($request->target === 'reply' || $request->target === 'comment') {
            $thread->messages()->create([
                'user_id' => $request->user()->id,
                'content' => $content
            ]);
            return response()->json(['success' => true, 'message' => 'Respuesta publicada en el hilo.']);
        }

        return response()->json(['success' => false, 'message' => 'Destino no válido.']);
    }

    public function transferGlobalContent(Request $request, \App\Models\Team $team)
    {
        $request->validate([
            'content' => 'required|string',
            'target' => 'required|string|in:comment,reply',
            'title' => 'nullable|string|max:255'
        ]);

        $content = $this->extractPayload($request->input('content'));

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

    private function extractPayload($content)
    {
        if (preg_match('/\[PAYLOAD\](.*?)\[\/PAYLOAD\]/s', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return trim(str_replace(['[PAYLOAD]', '[/PAYLOAD]', '[INJECT]', '[/INJECT]'], '', $content));
    }
}
