<?php

namespace App\Http\Controllers\Microsite;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use App\Models\MicrositeImage;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controlador para la gestión de imágenes multimedia adjuntas a un micrositio.
 */
class MicrositeImageController extends Controller
{
    /**
     * Verifica que el usuario tenga acceso de edición al micrositio del equipo.
     */
    protected function authorizeAccess(Team $team, Microsite $microsite): void
    {
        $user = auth()->user();
        if ((int) $microsite->team_id !== (int) $team->id || (int) $microsite->user_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para gestionar los archivos de este micrositio.');
        }
    }

    /**
     * Lista todas las imágenes asociadas al micrositio.
     */
    public function index(Team $team, Microsite $microsite): JsonResponse
    {
        $this->authorizeAccess($team, $microsite);

        return response()->json([
            'success' => true,
            'images' => $microsite->images()->get(),
        ]);
    }

    /**
     * Sube y almacena una nueva imagen en el micrositio.
     */
    public function store(Request $request, Team $team, Microsite $microsite): JsonResponse
    {
        $this->authorizeAccess($team, $microsite);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240',
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('image');
        $originalName = $file->getClientOriginalName();
        $title = $request->filled('title') ? trim($request->input('title')) : pathinfo($originalName, PATHINFO_FILENAME);

        $path = $file->store("microsites/{$microsite->id}/images", 'public');

        $image = $microsite->images()->create([
            'team_id' => $team->id,
            'user_id' => auth()->id(),
            'title' => $title,
            'file_name' => $originalName,
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Imagen subida correctamente.',
            'image' => $image,
        ], 201);
    }

    /**
     * Actualiza el título / pie descriptivo de la imagen.
     */
    public function update(Request $request, Team $team, Microsite $microsite, MicrositeImage $image): JsonResponse
    {
        $this->authorizeAccess($team, $microsite);

        if ($image->microsite_id !== $microsite->id) {
            abort(404, 'La imagen no pertenece a este micrositio.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Título de la imagen actualizado.',
            'image' => $image->fresh(),
        ]);
    }

    /**
     * Elimina una imagen del micrositio tanto del almacenamiento físico como de la base de datos.
     */
    public function destroy(Team $team, Microsite $microsite, MicrositeImage $image): JsonResponse
    {
        $this->authorizeAccess($team, $microsite);

        if ($image->microsite_id !== $microsite->id) {
            abort(404, 'La imagen no pertenece a este micrositio.');
        }

        $image->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.',
        ]);
    }
}
