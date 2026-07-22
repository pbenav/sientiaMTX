<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Services\ActivityService;

class ActivityAttachmentController extends Controller
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function uploadAttachment(Request $request, Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('attach', $activity)) {
            abort(403);
        }

        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'file|max:' . (\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024),
        ]);

        $totalUploadSize = collect($request->file('attachments'))->sum(fn($file) => $file->getSize());
        if (!$team->hasAvailableQuota($totalUploadSize)) {
            return back()->withErrors(['attachments' => '⚠️ El equipo ha alcanzado su límite de almacenamiento.']);
        }

        $this->activityService->handleAttachments($activity, $request->file('attachments'));

        return back()->with('success', __('activities.files_uploaded'));
    }

    /**
     * Elimina un adjunto.
     */
    public function deleteAttachment(Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id || auth()->user()->cannot('update', $activity)) {
            abort(403);
        }

        $this->activityService->deleteAttachment($attachment);

        return back()->with('success', __('activities.file_deleted'));
    }

    /**
     * Descarga un adjunto.
     */
    public function downloadAttachment(Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id) {
            abort(404);
        }

        if (auth()->user()->cannot('view', $activity)) {
            abort(403, 'No tienes permiso para acceder a este archivo.');
        }

        if ($attachment->disk === 'google_drive') {
            if ($attachment->file_path && filter_var($attachment->file_path, FILTER_VALIDATE_URL)) {
                return redirect()->away($attachment->file_path);
            }
            abort(404, 'Enlace de Google Drive no válido.');
        }

        $isValidPath = \Illuminate\Support\Str::startsWith($attachment->file_path, "activities/{$activity->id}/") ||
                       \Illuminate\Support\Str::startsWith($attachment->file_path, "attachments/activities/{$activity->id}/") ||
                       \Illuminate\Support\Str::startsWith($attachment->file_path, "attachments/tasks/{$activity->id}/") ||
                       \Illuminate\Support\Str::startsWith($attachment->file_path, "tasks/{$activity->id}/");
        if (!$isValidPath) {
            abort(403, 'Ruta de archivo no válida.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            return back()->with('error', __('activities.file_not_found'));
        }

        return \Illuminate\Support\Facades\Storage::disk($attachment->disk)->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Visualiza un adjunto.
     */
    public function viewAttachment(Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id) {
            abort(404);
        }

        if (auth()->user()->cannot('view', $activity)) {
            abort(403, 'No tienes permiso para acceder a este archivo.');
        }

        if ($attachment->disk === 'google_drive') {
            if ($attachment->web_view_link) {
                return redirect()->away($attachment->web_view_link);
            }
            abort(404, 'Enlace de Google Drive no válido.');
        }

        $isValidPath = \Illuminate\Support\Str::startsWith($attachment->file_path, "activities/{$activity->id}/") ||
                       \Illuminate\Support\Str::startsWith($attachment->file_path, "attachments/activities/{$activity->id}/") ||
                       \Illuminate\Support\Str::startsWith($attachment->file_path, "attachments/tasks/{$activity->id}/") ||
                       \Illuminate\Support\Str::startsWith($attachment->file_path, "tasks/{$activity->id}/");
        if (!$isValidPath) {
            abort(403, 'Ruta de archivo no válida.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            abort(404, 'El archivo no se encuentra en el servidor.');
        }

        return \Illuminate\Support\Facades\Storage::disk($attachment->disk)->response($attachment->file_path);
    }

    /**
     * Renombra un adjunto.
     */
    public function updateAttachment(Request $request, Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id) {
            abort(404);
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403);
        }

        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
        ]);

        $oldName = $attachment->file_name;
        $attachment->update([
            'file_name' => $validated['file_name'],
        ]);

        \App\Models\AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'rename',
            'metadata' => [
                'old_name' => $oldName,
                'new_name' => $validated['file_name']
            ],
            'ip_address' => request()->ip()
        ]);

        return back()->with('success', 'Archivo renombrado correctamente.');
    }

    /**
     * Reemplaza el contenido de un adjunto.
     */
    public function replaceAttachmentContent(Request $request, Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id) {
            abort(404);
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403);
        }

        if ($attachment->storage_provider !== 'local') {
            return response()->json(['success' => false, 'message' => 'Solo se pueden editar archivos locales.'], 400);
        }

        $request->validate([
            'file' => 'required|file|max:' . (\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024),
        ]);

        $newFile = $request->file('file');
        $oldSize = $attachment->file_size;
        $newSize = $newFile->getSize();

        if ($newSize > $oldSize) {
            $difference = $newSize - $oldSize;
            if (!$team->hasAvailableQuota($difference)) {
                return response()->json(['success' => false, 'message' => '⚠️ El equipo ha alcanzado su límite de almacenamiento.'], 403);
            }
        }

        // Store new file in the same directory structure
        $path = $newFile->store("activities/{$activity->id}", 'local');

        // Delete old file
        if (\Illuminate\Support\Facades\Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            \Illuminate\Support\Facades\Storage::disk($attachment->disk)->delete($attachment->file_path);
        }

        // Update database
        $attachment->update([
            'file_path' => $path,
            'file_size' => $newSize,
            'mime_type' => $newFile->getMimeType(),
        ]);

        \App\Models\AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'edit',
            'metadata' => [
                'old_size' => $oldSize,
                'new_size' => $newSize
            ],
            'ip_address' => request()->ip()
        ]);

        return response()->json([
            'success' => true,
            'attachment' => $attachment
        ]);
    }

    /**
     * Convierte una actividad a un nuevo tipo.
     */

}
