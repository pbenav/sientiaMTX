<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Storage;

class PublicAttachmentController extends Controller
{
    /**
     * Sirve un adjunto de forma pública mediante token permanente (para micrositios).
     */
    public function embed(TaskAttachment $attachment, string $token)
    {
        if (!hash_equals($attachment->getEmbedToken(), $token)) {
            abort(403, 'Enlace de archivo no válido.');
        }

        if ($attachment->storage_provider === 'google') {
            $previewUrl = str_replace('/view', '/preview', $attachment->web_view_link ?? '');
            if ($previewUrl) {
                return redirect()->away($previewUrl);
            }
            abort(404, 'Archivo no disponible.');
        }

        // Copia pública independiente: el original de la tarea nunca se mueve ni se elimina aquí
        $servePath = $attachment->ensurePublicCopy() ?? $attachment->file_path;

        if (!$servePath || !Storage::disk('public')->exists($servePath)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->response(
            $servePath,
            $attachment->file_name,
            [
                'Content-Disposition' => 'inline; filename="' . addslashes($attachment->file_name) . '"',
            ]
        );
    }
}
