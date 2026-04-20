<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\Google\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AttachmentLog;

class GoogleDriveController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Check if a team is linked to Google
     */
    protected function isTeamLinked(User $user, Team $team): bool
    {
        return $user->teams()
            ->where('team_id', $team->id)
            ->wherePivotNotNull('google_token')
            ->exists();
    }

    /**
     * Upload an existing local attachment to Google Drive
     */
    public function uploadToDrive(Team $team, TaskAttachment $attachment)
    {
        if ($attachment->task->team_id !== $team->id) {
            return back()->with('warning', 'Esta adjunto no pertenece a este equipo.');
        }

        $user = auth()->user();

        if ($user->cannot('view', $attachment->task)) {
            return back()->with('warning', 'No tienes permiso para gestionar este archivo.');
        }

        if (!$this->isTeamLinked($user, $team)) {
            return back()->with('error', 'Este equipo no tiene vinculada una cuenta de Google Workspace.');
        }

        if ($attachment->storage_provider === 'google') {
            return back()->with('info', 'Este archivo ya está en Google Drive.');
        }

        // 1. Get local file path
        $localPath = Storage::disk('public')->path($attachment->file_path);
        
        if (!file_exists($localPath)) {
            Log::error('Local file not found for Drive migration: ' . $localPath);
            return back()->with('error', 'El archivo local no existe o no se puede encontrar.');
        }

        try {
            // 2. Get/Create Sientia Folder in Drive for this Team account
            $folderId = $this->driveService->getOrCreateSientiaFolder($user, $team->id);

            // 3. Upload to Drive
            $result = $this->driveService->uploadFileFromPath($user, $localPath, $attachment->file_name, $folderId, $team->id);

            if ($result) {
                // 4. Update Attachment record
                $attachment->update([
                    'storage_provider' => 'google',
                    'provider_file_id' => $result['id'],
                    'web_view_link' => $result['webViewLink'],
                ]);

                // 5. Delete local file (User asked to "move")
                Storage::disk('public')->delete($attachment->file_path);

                AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => $user->id,
                    'action' => 'move_to_drive',
                    'metadata' => [
                        'file_id' => $result['id']
                    ],
                    'ip_address' => request()->ip()
                ]);

                return back()->with('success', 'Archivo movido a Google Drive correctamente.');
            }

            return back()->with('error', 'Hubo un error al subir el archivo a Google Drive.');

        } catch (\Exception $e) {
            Log::error('Drive Migration Error: ' . $e->getMessage());
            return back()->with('error', 'Error en la migración: ' . $e->getMessage());
        }
    }

    /**
     * Save AI Response to Google Drive
     */
    public function saveAiResponse(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = $request->user();
        $team = Team::findOrFail($request->team_id);

        if (!$this->isTeamLinked($user, $team)) {
            return response()->json(['success' => false, 'message' => 'Este equipo no tiene Google vinculado']);
        }

        try {
            $folderId = $this->driveService->getOrCreateSientiaFolder($user, $team->id);
            $datePrefix = date('Y-m-d-');
            $filename = $datePrefix . 'Respuesta Ax.ia - ' . date('H:i:s') . '.docx';
            
            $result = $this->driveService->createFileFromText($user, $filename, $request->content, $folderId, true, $team->id);

            if ($result) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Guardado en Google Drive!',
                    'link' => $result['webViewLink']
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Fallo al crear el archivo.']);

        } catch (\Exception $e) {
            Log::error('Save Response to Drive Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * List Google Drive contents (AJAX)
     */
    public function listContents(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = $request->user();
        $team = Team::findOrFail($request->team_id);
        $folderId = $request->query('folderId') ?: 'root';
        
        if (!$this->isTeamLinked($user, $team)) {
            return response()->json(['success' => false, 'message' => 'Equipo no vinculado'], 403);
        }

        $query = "'{$folderId}' in parents and trashed = false";
        $files = $this->driveService->listFiles($user, $query, 20, $team->id);

        return response()->json([
            'files' => $files,
            'currentFolderId' => $folderId
        ]);
    }

    /**
     * Link an existing Drive file to a Task
     */
    public function attachFromDrive(Team $team, Task $task, Request $request)
    {
        if ($task->team_id !== $team->id) {
            return response()->json(['success' => false, 'message' => 'La tarea no pertenece a este equipo.'], 403);
        }

        if (auth()->user()->cannot('view', $task)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para ver esta tarea.'], 403);
        }

        $request->validate([
            'file_id' => 'required|string',
            'file_name' => 'required|string',
            'web_view_link' => 'required|url',
            'mime_type' => 'nullable|string',
        ]);

        try {
            $attachment = TaskAttachment::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'file_name' => $request->file_name,
                'file_path' => 'google_drive/' . $request->file_id, // Virtual path
                'file_size' => $request->file_size ?? 0,
                'mime_type' => $request->mime_type ?? 'application/octet-stream',
                'storage_provider' => 'google',
                'provider_file_id' => $request->file_id,
                'web_view_link' => $request->web_view_link,
            ]);

            return response()->json([
                'success' => true,
                'attachment' => $attachment
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Deprecated OAuth methods - Unified under GoogleController
     */
    public function redirect() { return $this->deprecated(); }
    public function callback() { return $this->deprecated(); }
    public function disconnect() { return $this->deprecated(); }

    protected function deprecated()
    {
        return Redirect::route('profile.edit', ['tab' => 'integrations'])
            ->with('info', 'Usa el panel de integraciones para gestionar tu cuenta de Google.');
    }
}
