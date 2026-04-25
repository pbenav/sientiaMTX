<?php

namespace App\Http\Controllers;

use App\Contracts\AiAssistantInterface;
use App\Models\QuickNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QuickNoteController extends Controller
{
    protected $ai;

    public function __construct(AiAssistantInterface $ai)
    {
        $this->ai = $ai;
    }
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

    public function uploadAttachment(Request $request, QuickNote $quick_note)
    {
        $this->authorize('update', $quick_note);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB limit
        ]);

        $file = $request->file('file');
        $path = $file->store('quick-notes/' . auth()->id(), 'public');
        
        $attachments = $quick_note->attachments ?? [];
        $attachments[] = [
            'id' => uniqid(),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::url($path),
            'type' => $file->getMimeType(),
            'created_at' => now(),
        ];

        $quick_note->update(['attachments' => $attachments]);

        return response()->json($quick_note);
    }

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

    public function transcribeAttachment(Request $request, QuickNote $quick_note, $attachmentId)
    {
        Log::info("Transcripción solicitada", ['note_id' => $quick_note->id, 'att_id' => $attachmentId]);
        $this->authorize('update', $quick_note);

        $attachments = $quick_note->attachments ?? [];
        $targetAtt = null;
        
        foreach ($attachments as $att) {
            if (($att['id'] ?? null) === $attachmentId) {
                $targetAtt = $att;
                break;
            }
        }

        if (!$targetAtt || !str_starts_with($targetAtt['type'], 'audio/')) {
            Log::warning("Ax.ia Transcripción: Adjunto no válido", ['id' => $attachmentId, 'found' => !!$targetAtt]);
            return response()->json(['message' => 'Audio no encontrado o tipo inválido.'], 422);
        }

        try {
            Log::info("Ax.ia Transcripción: Iniciando...", ['file' => $targetAtt['path']]);
            $filePath = Storage::disk('public')->path($targetAtt['path']);
            
            if (!file_exists($filePath)) {
                Log::error("Ax.ia Transcripción: El archivo físico no existe en " . $filePath);
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
                } catch (\Exception $e) {}
            }

            return response()->json([
                'transcription' => trim($transcription),
                'attachment_id' => $attachmentId
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error en la transcripción: ' . $e->getMessage()], 500);
        }
    }
}
