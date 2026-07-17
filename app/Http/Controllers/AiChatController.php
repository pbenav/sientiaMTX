<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Contracts\AiAssistantInterface;
use Illuminate\Http\Request;

use App\Models\AiChatMessage;

class AiChatController extends Controller
{
    public function getAvailableModels(Request $request, AiAssistantInterface $aiService)
    {
        $aiService->forUser($request->user(), $request->team_id);

        // Si viene una clave en la request, la sobreescribimos para hacer la consulta con ella
        if ($request->api_key && method_exists($aiService, 'setTemporaryKey')) {
            $aiService->setTemporaryKey($request->api_key);
        }

        // Limpiar el caché de sesión del modelo funcional para esta clave al solicitar refresco de modelos
        if (method_exists($aiService, 'clearWorkingModelCache')) {
            $aiService->clearWorkingModelCache();
        }

        $models = $aiService->listAvailableModels();

        return response()->json([
            'models' => $models,
            'current_model' => $aiService->getTargetModel(), // Sin re-instanciar, usa el estado actual
        ]);
    }

    public function getHistory(Request $request)
    {
        $messages = AiChatMessage::where('user_id', $request->user()->id)
            ->where('team_id', $request->team_id)
            ->with('taskAttachment')
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
            'task_id' => 'nullable|integer',
            'attachment_id' => 'nullable|integer',
            'forum_thread_id' => 'nullable|integer|exists:forum_threads,id',
            'forum_message_id' => 'nullable|integer|exists:forum_messages,id',
            'file' => 'nullable|file|max:20480', // 20MB limit
            'reuse_file_path' => 'nullable|string|max:500',
            'reuse_file_name' => 'nullable|string|max:255',
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
            $task = \App\Models\Activity::find($request->task_id) ?? \App\Models\Task::find($request->task_id);
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

        if (!$prompt && !$request->hasFile('file') && !$request->reuse_file_path) {
            return response()->json(['message' => '¿En qué puedo ayudarte?'], 422);
        }

        if (!$prompt && ($request->hasFile('file') || $request->reuse_file_path)) {
            $prompt = 'Hola Ax.ia, he adjuntado un archivo. Por favor, analízalo y dime lo más relevante o responde a lo que contenga si es una instrucción.';
        }

        // 1. Persist User Message
        $contentToStore = $prompt;
        $filePath = null;
        $fileName = null;
        $fileType = null;
        $taskAttachmentId = null;

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
        } elseif ($request->reuse_file_path) {
            $rawPath = $request->reuse_file_path;

            if (str_starts_with($rawPath, 'attachments/')) {
                $taskAttachment = \App\Models\TaskAttachment::where('file_path', $rawPath)->first();
                $team = $request->team_id ? \App\Models\Team::find($request->team_id) : null;

                if ($taskAttachment && $team && $taskAttachment->canBeAccessedBy($user, $team)) {
                    $fullPath = storage_path('app/public/' . $rawPath);
                    if (file_exists($fullPath)) {
                        $mimeType = \Illuminate\Support\Facades\File::mimeType($fullPath);
                        $name = $request->reuse_file_name ?: $taskAttachment->file_name;

                        $mockFile = new \Illuminate\Http\UploadedFile($fullPath, $name, $mimeType, null, true);
                        $aiAssistant->withFile($mockFile);
                        $aiAssistant->withAttachmentContext($taskAttachment);

                        $taskAttachmentId = $taskAttachment->id;
                        $fileName = $name;
                        $fileType = $mimeType;

                        if (!str_starts_with($prompt, "📁 [Archivo:")) {
                            $contentToStore = "📁 [Archivo: " . $fileName . "]\n\n" . $prompt;
                        }
                    }
                }
            } elseif (
                AiChatMessage::isAiOwnedStoragePath($rawPath) &&
                AiChatMessage::where('user_id', $user->id)->where('file_path', $rawPath)->exists()
            ) {
                $fullPath = storage_path('app/public/' . $rawPath);
                if (file_exists($fullPath)) {
                    $mimeType = \Illuminate\Support\Facades\File::mimeType($fullPath);
                    $name = $request->reuse_file_name ?: basename($fullPath);

                    $mockFile = new \Illuminate\Http\UploadedFile($fullPath, $name, $mimeType, null, true);
                    $aiAssistant->withFile($mockFile);

                    $filePath = $rawPath;
                    $fileName = $name;
                    $fileType = $mimeType;

                    if (!str_starts_with($prompt, "📁 [Archivo:")) {
                        $contentToStore = "📁 [Archivo: " . $fileName . "]\n\n" . $prompt;
                    }
                }
            }
        }

        $userMessage = AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'task_attachment_id' => $taskAttachmentId,
            'role' => 'user',
            'content' => $contentToStore,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);

        $aiAssistant->forUser($user, $request->team_id);

        // (withFile already called above if present)

        if ($request->task_id) {
            $task = \App\Models\Activity::find($request->task_id) ?? \App\Models\Task::find($request->task_id);
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
            $attachment = \App\Models\ActivityAttachment::find($request->attachment_id) ?? \App\Models\TaskAttachment::find($request->attachment_id);
            if ($attachment) {
                $team = \App\Models\Team::find($request->team_id);
                if (!$team || !$attachment->canBeAccessedBy($user, $team)) {
                    return response()->json(['message' => 'No tienes permiso para analizar este archivo.'], 403);
                }

                $aiAssistant->withAttachmentContext($attachment);
                
                // Referenciar el adjunto
                if (!$filePath && !$taskAttachmentId) {
                    $updateData = [
                        'file_name' => $attachment->file_name,
                        'file_type' => $attachment->mime_type,
                        'file_path' => null,
                    ];
                    
                    // Si es el antiguo TaskAttachment mantenemos la referencia FK
                    if ($attachment instanceof \App\Models\TaskAttachment) {
                        $updateData['task_attachment_id'] = $attachment->id;
                    } else {
                        // Si es el nuevo ActivityAttachment, lo copiamos como ruta reciclada 
                        // para que siga accesible sin requerir esquema nuevo en ai_chat_messages
                        $updateData['file_path'] = $attachment->file_path;
                    }
                    
                    $userMessage->update($updateData);
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
                    if ($user->cannot('view', $thread->task)) {
                        return response()->json(['message' => 'No tienes permiso para acceder al contenido de esta tarea.'], 403);
                    }
                }

                $message = $request->forum_message_id ? \App\Models\ForumMessage::find($request->forum_message_id) : null;
                $aiAssistant->withForumContext($thread, $message);
            }
        }
        
        // Cargar el historial de la conversación (hasta 15 mensajes anteriores)
        $history = AiChatMessage::where('user_id', $user->id)
            ->where('team_id', $request->team_id)
            ->latest()
            ->limit(15) // Limitamos para no saturar el contexto
            ->get()
            ->reverse()
            ->values();
            
        // Quitamos el mensaje que acabamos de crear (el prompt actual) porque lo enviamos como $prompt
        $history = $history->filter(function($msg) use ($contentToStore) {
            return $msg->content !== $contentToStore;
        })->values();

        if ($history->isNotEmpty()) {
            $aiAssistant->withHistory($history);
        }
        
        $response = $aiAssistant->generateText($prompt);
        
        // --- RUTINA DE AUTO-CORRECCIÓN DE PAYLOADS CORRUPTOS ---
        // Si detectamos que la IA devolvió un bloque [PAYLOAD] pero tiene sintaxis JSON rota, 
        // re-preguntamos silenciosamente para intentar sanarlo antes de que el usuario lo vea.
        if (str_contains($response, '[PAYLOAD]')) {
            preg_match('/\[PAYLOAD\](.*?)\[\/PAYLOAD\]/s', $response, $matches);
            $rawPayload = isset($matches[1]) ? trim($matches[1]) : '';
            $rawPayload = preg_replace('/^```\w*\n/', '', $rawPayload);
            $rawPayload = preg_replace('/```$/', '', trim($rawPayload));
            
            // Sanidad previa para control-chars (simulando lo que hace el front)
            $safeRaw = preg_replace_callback('/"([^"\\\\]*(\\\\.[^"\\\\]*)*)"/s', function($m) {
                return str_replace(["\n", "\r", "\t"], ["\\n", "\\r", "\\t"], $m[0]);
            }, $rawPayload);
            
            json_decode($safeRaw);
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Illuminate\Support\Facades\Log::warning("Ax.ia [{$user->email}] emitió un JSON corrupto (" . json_last_error_msg() . "). Iniciando ciclo de auto-sanación...");
                
                // Para la rutina de sanación incluimos explícitamente el JSON corrupto en el prompt.
                // Así la IA no necesita adivinar el contexto previo y tiene los datos reales.
                $repairPrompt = "A continuación te presento un bloque JSON que está corrupto, incompleto o mal cerrado.\n" .
                               "Por favor, corrígelo y complétalo para que sea un JSON 100% VÁLIDO, cerrando todas las llaves y comillas.\n" .
                               "Devuelve ÚNICAMENTE el bloque JSON corregido envuelto en etiquetas [PAYLOAD] y [/PAYLOAD].\n\n" .
                               "JSON CORRUPTO A REPARAR:\n" . $rawPayload;
                
                try {
                    // IMPORTANTE: Creamos una instancia LIMPIA del asistente de IA para la sanación.
                    // De lo contrario, el asistente original volvería a subir el archivo PDF y sus contextos pesados 
                    // en esta segunda llamada, provocando lentitud extrema y detonando el Error 504 Gateway Timeout del servidor.
                    $cleanAssistant = app(\App\Contracts\AiAssistantInterface::class)->forUser($user, $request->team_id);
                    $repairResult = $cleanAssistant->generateText($repairPrompt);
                    
                    // Si el modelo no envolvió en [PAYLOAD] la respuesta forzada, lo envolvemos nosotros
                    $healedChunk = str_contains($repairResult, '[PAYLOAD]') ? $repairResult : "[PAYLOAD]{$repairResult}[/PAYLOAD]";
                    
                    preg_match('/\[PAYLOAD\](.*?)\[\/PAYLOAD\]/s', $healedChunk, $hMatches);
                    $healedRaw = isset($hMatches[1]) ? trim($hMatches[1]) : '';
                    $healedRaw = preg_replace('/^```\w*\n/', '', $healedRaw);
                    $healedRaw = preg_replace('/```$/', '', trim($healedRaw));
                    
                    $safeHealed = preg_replace_callback('/"([^"\\\\]*(\\\\.[^"\\\\]*)*)"/s', function($m) {
                        return str_replace(["\n", "\r", "\t"], ["\\n", "\\r", "\\t"], $m[0]);
                    }, $healedRaw);
                    
                    json_decode($safeHealed);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // ¡Éxito rotundo! Reemplazamos la parte rota en la respuesta original con la versión reparada
                        $response = preg_replace('/\[PAYLOAD\].*?\[\/PAYLOAD\]/s', "[PAYLOAD]\n{$healedRaw}\n[/PAYLOAD]", $response);
                        \Illuminate\Support\Facades\Log::info("Ax.ia: Auto-sanación de JSON completada con éxito y de forma optimizada.");
                    } else {
                        \Illuminate\Support\Facades\Log::warning("Ax.ia: El intento de auto-sanación también ha fallado. Se servirá el error visual.");
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error en rutina de sanación de IA: " . $e->getMessage());
                }
            }
        }
        // ------------------------------------------------------
        
        // Human Recharge Logic
        if (str_contains($response, '[RECHARGE]')) {
            $user->increment('energy_level', 20);
            if ($user->energy_level > 100) {
                $user->update(['energy_level' => 100]);
            }
            $response = str_replace('[RECHARGE]', '', $response);
        }

        // 2. Persist AI Message
        $aiMessage = AiChatMessage::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'task_id' => $request->task_id,
            'role' => 'ai',
            'content' => $response,
        ]);

        return response()->json([
            'message' => $response,
            'current_model' => $aiAssistant->getTargetModel(),
            'user_message_id' => $userMessage->id,
            'ai_message_id' => $aiMessage->id,
        ]);
    }

    public function deleteMessage(Request $request, int $id)
    {
        $message = AiChatMessage::where('user_id', $request->user()->id)->findOrFail($id);

        $message->deleteOwnedFile();
        $message->delete();

        return response()->json(['success' => true]);
    }

    public function clearHistory(Request $request)
    {
        $messages = AiChatMessage::where('user_id', $request->user()->id)
            ->when($request->team_id, function($q) use ($request) {
                return $q->where('team_id', $request->team_id);
            })
            ->whereNotNull('file_path')
            ->get();

        foreach ($messages as $msg) {
            $msg->deleteOwnedFile();
        }

        AiChatMessage::where('user_id', $request->user()->id)
            ->when($request->team_id, function($q) use ($request) {
                return $q->where('team_id', $request->team_id);
            })
            ->delete();

        return response()->json(['success' => true]);
    }

}
