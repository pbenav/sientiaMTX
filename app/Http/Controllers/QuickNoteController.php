<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Contracts\AiAssistantInterface;
use App\Models\QuickNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para la gestión de notas rápidas del usuario (crear, editar, eliminar, adjuntos, transcripción).
 *
 * Soporta transcripción de audio mediante IA (Ax.ia), gestión de adjuntos con almacenamiento local,
 * y operaciones masivas (ocultar múltiples notas).
 */
class QuickNoteController extends Controller
{
    /**
     * Instancia del servicio de IA para transcripción.
     *
     * @var \App\Contracts\AiAssistantInterface
     */
    protected $ai;

    public function __construct(AiAssistantInterface $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Obtiene todas las notas rápidas del usuario autenticado.
     *
     * Corrige notas antiguas que carecen de 'id' en sus adjuntos asignando
     * un ID único generado con uniqid().
     *
     * @return \Illuminate\Http\JsonResponse Respuesta con array de notas del usuario
     */
    public function index()
    {
        $notes = auth()->user()->quickNotes;
        
        // Ensure all attachments have IDs for new features
        foreach ($notes as $note) {
            if ($note->attachments) {
                $dirty = false;
                $atts = $note->attachments;
                foreach ($atts as &$att) {
                    if (!isset($att['id'])) {
                        $att['id'] = uniqid();
                        $dirty = true;
                    }
                }
                unset($att);
                if ($dirty) {
                    $note->update(['attachments' => $atts]);
                }
            }
        }

        return response()->json($notes);
    }

    /**
     * Crea una nueva nota rápida para el usuario autenticado.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener content (opcional), position_x, position_y, width, height, color, is_pinned, is_minimized, is_hidden (todos opcionales)
     * @return \Illuminate\Http\JsonResponse Respuesta con la nota creada
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'nullable|string',
            'position_x' => 'sometimes|numeric',
            'position_y' => 'sometimes|numeric',
            'width' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'color' => 'sometimes|string',
            'is_pinned' => 'sometimes|boolean',
            'is_minimized' => 'sometimes|boolean',
            'is_hidden' => 'sometimes|boolean',
        ]);

        $note = auth()->user()->quickNotes()->create($validated);

        return response()->json($note);
    }

    /**
     * Actualiza una nota rápida existente.
     *
     * Verifica que el usuario sea el propietario de la nota mediante el policy 'update'.
     *
     * @param  \Illuminate\Http\Request  $request  Campos a actualizar (content, posición, color, estado)
     * @param  \App\Models\QuickNote  $quick_note  Nota a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta con la nota actualizada
     */
    public function update(Request $request, QuickNote $quick_note)
    {
        $this->authorize('update', $quick_note);

        $validated = $request->validate([
            'content' => 'nullable|string',
            'position_x' => 'sometimes|numeric',
            'position_y' => 'sometimes|numeric',
            'width' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'color' => 'sometimes|string',
            'is_pinned' => 'sometimes|boolean',
            'is_minimized' => 'sometimes|boolean',
            'is_hidden' => 'sometimes|boolean',
        ]);

        $quick_note->update($validated);

        return response()->json($quick_note);
    }

    /**
     * Elimina una nota rápida y sus archivos adjuntos del almacenamiento.
     *
     * Verifica que el usuario sea el propietario mediante el policy 'delete'.
     * Borra todos los archivos adjuntos del disco 'public' antes de eliminar el registro.
     *
     * @param  \App\Models\QuickNote  $quick_note  Nota a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true
     */
    public function destroy(QuickNote $quick_note)
    {
        $this->authorize('delete', $quick_note);
        
        // Clean up attachments if any
        if ($quick_note->attachments) {
            foreach ($quick_note->attachments as $attachment) {
                if (isset($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }
        }

        $quick_note->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Sube un archivo adjunto a una nota rápida.
     *
     * Almacena el archivo en storage/app/public/quick-notes/{user_id}/.
     * Detecta automáticamente si es una grabación de audio y ajusta el MIME type.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener file (máx 10MB)
     * @param  \App\Models\QuickNote  $quick_note  Nota a la que adjuntar el archivo
     * @return \Illuminate\Http\JsonResponse Respuesta con la nota actualizada incluyendo el nuevo adjunto
     */
    public function uploadAttachment(Request $request, QuickNote $quick_note)
    {
        $this->authorize('update', $quick_note);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB limit
        ]);

        $file = $request->file('file');
        $path = $file->store('quick-notes/' . auth()->id(), 'public');
        
        $mimeType = $file->getMimeType();
        // Forzar tipo audio si es una grabación de la app
        if (str_contains($file->getClientOriginalName(), 'note_recording')) {
            $mimeType = str_replace('video/', 'audio/', $mimeType);
        }

        $attachments = $quick_note->attachments ?? [];
        $attachments[] = [
            'id' => uniqid(),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'type' => $mimeType,
            'created_at' => now(),
        ];

        $quick_note->update(['attachments' => $attachments]);

        return response()->json($quick_note);
    }

    /**
     * Elimina un adjunto específico de una nota rápida.
     *
     * Borra el archivo del disco 'public' y lo remueve del array de attachments.
     *
     * @param  \Illuminate\Http\Request  $request  Debe estar autenticado
     * @param  \App\Models\QuickNote  $quick_note  Nota que contiene el adjunto
     * @param  string  $attachmentId  ID del adjunto a eliminar (uniqid)
     * @return \Illuminate\Http\JsonResponse Respuesta con la nota actualizada
     */
    public function deleteAttachment(Request $request, QuickNote $quick_note, $attachmentId)
    {
        $this->authorize('update', $quick_note);

        $attachments = $quick_note->attachments ?? [];
        $newAttachments = [];
        
        foreach ($attachments as $att) {
            if (($att['id'] ?? null) === $attachmentId) {
                Storage::disk('public')->delete($att['path']);
                continue;
            }
            $newAttachments[] = $att;
        }

        $quick_note->update(['attachments' => $newAttachments]);

        return response()->json($quick_note);
    }

    /**
     * Transcribe un archivo de audio adjunto a una nota rápida usando IA (Ax.ia).
     *
     * Lee el archivo del disco público, lo envía al servicio de IA con una instrucción
     * de transcripción, limpia etiquetas de respuesta ([PAYLOAD], JSON wrappers),
     * y retorna el texto transcrito.
     *
     * @param  \Illuminate\Http\Request  $request  Debe estar autenticado
     * @param  \App\Models\QuickNote  $quick_note  Nota que contiene el audio
     * @param  string  $attachmentId  ID del adjunto de audio a transcribir
     * @return \Illuminate\Http\JsonResponse Respuesta con transcription y attachment_id, o error 404/422/500
     */
    public function transcribeAttachment(Request $request, QuickNote $quick_note, $attachmentId)
    {
        \Log::info("Transcripción solicitada", ['note_id' => $quick_note->id, 'att_id' => $attachmentId]);
        $this->authorize('update', $quick_note);

        $attachments = $quick_note->attachments ?? [];
        $targetAtt = null;
        
        foreach ($attachments as $att) {
            if (($att['id'] ?? null) === $attachmentId) {
                $targetAtt = $att;
                break;
            }
        }

        if (!$targetAtt || (!str_starts_with($targetAtt['type'], 'audio/') && !str_contains($targetAtt['type'], 'webm'))) {
            \Log::warning("Ax.ia Transcripción: Adjunto no válido", ['id' => $attachmentId, 'found' => !!$targetAtt, 'type' => $targetAtt['type'] ?? 'none']);
            return response()->json(['message' => 'Audio no encontrado o tipo inválido.'], 422);
        }

        try {
            \Log::info("Ax.ia Transcripción: Iniciando...", ['file' => $targetAtt['path']]);
            $filePath = Storage::disk('public')->path($targetAtt['path']);
            
            if (!file_exists($filePath)) {
                \Log::error("Ax.ia Transcripción: El archivo físico no existe en " . $filePath);
                return response()->json(['message' => 'El archivo de audio no existe físicamente en el servidor.'], 404);
            }
            $file = new \Illuminate\Http\UploadedFile(
                $filePath, 
                $targetAtt['name'], 
                $targetAtt['type'], 
                null, 
                true
            );

            $transcription = $this->ai
                ->forUser(auth()->user())
                ->withFile($file)
                ->generateText("Transcribe este audio íntegramente. Solo devuelve el texto transcrito, sin preámbulos ni comentarios. Si no hay voz, di [Sin voz detectable].");

            // Limpiar posibles etiquetas de la IA
            $transcription = str_replace(['[PAYLOAD]', '[/PAYLOAD]'], '', $transcription);
            if (str_contains($transcription, '{')) {
                // Si la IA devolvió un JSON por costumbre, intentamos extraer el contenido
                try {
                    $json = json_decode($transcription, true);
                    $transcription = $json['content'] ?? $json['text'] ?? $transcription;
                } catch (\Throwable $e) {}
            }

            return response()->json([
                'transcription' => trim($transcription),
                'attachment_id' => $attachmentId
            ]);
        } catch (\Throwable $e) {
            \Log::error("Error en transcripción QuickNote: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Error en la transcripción: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualización masiva de notas rápidas (ocultar).
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener ids (array de IDs válidos), is_hidden (boolean)
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:quick_notes,id',
            'is_hidden' => 'required|boolean',
        ]);

        auth()->user()->quickNotes()
            ->whereIn('id', $validated['ids'])
            ->update(['is_hidden' => $validated['is_hidden']]);

        return response()->json(['success' => true]);
    }
}
