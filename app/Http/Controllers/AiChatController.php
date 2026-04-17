<?php

namespace App\Http\Controllers;

use App\Contracts\AiAssistantInterface;
use Illuminate\Http\Request;

use App\Models\AiChatMessage;

class AiChatController extends Controller
{
    public function getHistory(Request $request)
    {
        $messages = AiChatMessage::where('user_id', $request->user()->id)
            ->when($request->team_id, function($q) use ($request) {
                return $q->where('team_id', $request->team_id);
            })
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
            'prompt' => 'required|string|max:2000',
            'team_id' => 'nullable|integer|exists:teams,id',
            'task_id' => 'nullable|integer|exists:tasks,id'
        ]);

        $user = $request->user();

        // 1. Persist User Message
        AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'role' => 'user',
            'content' => $request->prompt,
        ]);

        $aiAssistant->forUser($user, $request->team_id);

        if ($request->task_id) {
            $task = \App\Models\Task::find($request->task_id);
            if ($task) {
                $aiAssistant->withTaskContext($task);
            }
        }
        
        $response = $aiAssistant->generateText($request->prompt);

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
            'target' => 'required|string|in:description,observations,comment'
        ]);

        $content = $request->content;

        // Smart Extraction: search for [PAYLOAD] tags
        if (preg_match('/\[PAYLOAD\](.*?)\[\/PAYLOAD\]/s', $content, $matches)) {
            $content = trim($matches[1]);
        } else {
            // Clean up potentially failed tags or just use the whole content
            $content = str_replace(['[PAYLOAD]', '[/PAYLOAD]', '[INJECT]', '[/INJECT]'], '', $content);
        }

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
                    'title' => 'Discusión: ' . $rootTask->title,
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
}
