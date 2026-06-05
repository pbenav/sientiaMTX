<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\AttachmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TaskAttachmentController extends Controller
{
    public function uploadAttachment(Request $request, Team $team, Task $task)
    {
        if (auth()->user()->cannot('view', $team)) {
            return back()->with('error', __('teams.unauthorized_access'));
        }

        if ($task->team_id !== $team->id) {
            abort(404);
        }

        $maxSizeKB = (int)ini_get('upload_max_filesize') * 1024;
        
        if ($request->hasFile('files')) {
            $request->validate([
                'files' => 'required|array',
                'files.*' => "file|max:$maxSizeKB",
            ]);
            $files = $request->file('files');
        } else {
            $request->validate([
                'file' => "required|file|max:$maxSizeKB",
            ]);
            $files = [$request->file('file')];
        }

        $user = auth()->user();
        $uploaded = 0;

        foreach ($files as $file) {
            $size = $file->getSize();

            // Check user quota
            if (!$user->hasAvailableQuota($size)) {
                return back()->with('error', 'Has excedido tu cuota de espacio en disco.');
            }

            // Check TEAM quota
            if (!$team->hasAvailableQuota($size)) {
                return back()->with('error', '⚠️ El equipo ha alcanzado su límite de almacenamiento. Un coordinador debe liberar espacio antes de poder subir más archivos.');
            }

            $path = $file->store("attachments/task_{$task->id}", 'public');

            $originalName = $file->getClientOriginalName();
            $datePrefix = date('Y-m-d-');
            $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

            $attachment = $task->attachments()->create([
                'user_id' => $user->id,
                'file_name' => $fileName,
                'file_path' => $path,
                'file_size' => $size,
                'mime_type' => $file->getMimeType(),
            ]);

            AttachmentLog::create([
                'attachment_id' => $attachment->id,
                'user_id' => $user->id,
                'action' => 'upload',
                'metadata' => [
                    'original_name' => $originalName,
                    'size' => $size
                ],
                'ip_address' => request()->ip()
            ]);

            // Update user disk usage
            $user->increment('disk_used', $size);
            $uploaded++;
        }

        return back()->with('success', "Se han adjuntado $uploaded archivo(s) correctamente.");
    }

    public function downloadAttachment(Team $team, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($team, $attachment);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo no se encuentra en el servidor.');
        }

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'download',
            'ip_address' => request()->ip()
        ]);

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function viewAttachment(Team $team, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($team, $attachment);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo no se encuentra en el servidor.');
        }

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'view',
            'ip_address' => request()->ip()
        ]);

        return Storage::disk('public')->response($attachment->file_path);
    }

    protected function authorizeAttachmentAccess(Team $team, TaskAttachment $attachment)
    {
        if (!$attachment->canBeAccessedBy(auth()->user(), $team)) {
            abort(403, 'No tienes permiso para acceder a este archivo.');
        }
    }

    public function updateAttachment(Request $request, Team $team, TaskAttachment $attachment)
    {
        $this->authorize('update', $attachment);

        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
        ]);

        $oldName = $attachment->file_name;
        $attachment->update([
            'file_name' => $validated['file_name'],
        ]);

        AttachmentLog::create([
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

    public function destroyAttachment(Team $team, TaskAttachment $attachment)
    {
        $this->authorize('delete', $attachment);

        // Log deletion BEFORE deleting the attachment record (due to cascade)
        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'delete',
            'metadata' => [
                'file_name' => $attachment->file_name,
                'storage_provider' => $attachment->storage_provider
            ],
            'ip_address' => request()->ip()
        ]);

        // Remove file from disk if exists
        if ($attachment->storage_provider === 'local' && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
            
            // Update user disk usage (decrement) only if file existed and was deleted
            if ($attachment->user) {
                $attachment->user->decrement('disk_used', $attachment->file_size);
            }
        } elseif ($attachment->storage_provider === 'google' && $attachment->provider_file_id) {
            // ONLY delete from Google Drive if explicitly requested
            if (request()->boolean('delete_from_drive')) {
                try {
                    $googleService = app(\App\Services\Google\GoogleDriveService::class);
                    $googleService->deleteFile(auth()->user(), $attachment->provider_file_id, $team->id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete from Google Drive during attachment destruction: ' . $e->getMessage());
                }
            }
        }
        
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    /**
     * Get attachment history logs
     */
    public function attachmentHistory(Team $team, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($team, $attachment);

        $attachment->load('user');
        $logs = AttachmentLog::where('attachment_id', $attachment->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'attachment' => $attachment,
            'logs' => $logs
        ]);
    }
}
