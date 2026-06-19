<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\ForumMessage;
use App\Models\ForumThread;
use App\Models\Team;
use App\Models\User;
use App\Notifications\NewForumMessageNotification;
use App\Models\AttachmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ForumMessageController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Team $team, ForumThread $thread)
    {
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        if ($thread->is_locked) {
            return back()->with('error', __('forum.thread_locked'));
        }

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'parent_id' => 'nullable|exists:forum_messages,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . ((int)ini_get('upload_max_filesize') * 1024),
            'drive_attachments' => 'nullable|string',
            'is_private' => 'nullable|boolean',
        ]);

        $message = $thread->messages()->create([
            'user_id' => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
            'is_private' => $request->boolean('is_private'),
        ]);

        // Handle Local Attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $originalName = $file->getClientOriginalName();
                $datePrefix = date('Y-m-d-');
                $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

                $attachment = $message->attachments()->create([
                    'user_id' => auth()->id(),
                    'file_path' => $path,
                    'file_name' => $fileName,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => auth()->id(),
                    'action' => 'upload',
                    'metadata' => [
                        'original_name' => $originalName,
                        'size' => $file->getSize()
                    ],
                    'ip_address' => request()->ip()
                ]);
            }
        }

        // Handle Drive/AJAX Attachments
        if ($request->has('drive_attachments') && !empty($request->drive_attachments)) {
            $driveFiles = json_decode($request->drive_attachments, true);
            if (is_array($driveFiles)) {
                foreach ($driveFiles as $file) {
                    $isLocal = isset($file['provider']) && $file['provider'] === 'local';
                    
                    $attachment = $message->attachments()->create([
                        'user_id' => auth()->id(),
                        'file_name' => $file['name'],
                        'file_path' => $isLocal ? ($file['path'] ?? '') : 'google_drive/' . $file['id'],
                        'file_size' => $file['size'] ?? 0,
                        'mime_type' => $file['mimeType'] ?? ($file['mime_type'] ?? 'application/octet-stream'),
                        'storage_provider' => $isLocal ? 'local' : 'google',
                        'provider_file_id' => $isLocal ? null : $file['id'],
                        'web_view_link' => $isLocal ? null : $file['webViewLink'],
                    ]);

                    AttachmentLog::create([
                        'attachment_id' => $attachment->id,
                        'user_id' => auth()->id(),
                        'action' => $isLocal ? 'upload' : 'drive_migration',
                        'metadata' => [
                            'file_id' => $isLocal ? null : $file['id'],
                            'source' => $isLocal ? 'local_ajax' : 'google_drive'
                        ],
                        'ip_address' => request()->ip()
                    ]);
                }
            }
        }

        // Touch the thread to push it to the top
        $thread->touch();

        // --- Multi-Stakeholder & Mention Notification Logic ---
        $recipients = collect();
        
        // 1. Thread creator (always notified of new activity)
        if ($thread->user) {
            $recipients->push($thread->user);
        }
        
        // 2. Task-related stakeholders
        if ($thread->task) {
            $rootTask = $thread->task;
            while ($rootTask->parent_id && $rootTask->parent) {
                $rootTask = $rootTask->parent;
            }
            
            $collectStakeholders = function($task) use (&$recipients, &$collectStakeholders) {
                if ($task->creator) $recipients->push($task->creator);
                if ($task->assignedUser) $recipients->push($task->assignedUser);
                if ($task->assignedTo) {
                    foreach ($task->assignedTo as $user) {
                        $recipients->push($user);
                    }
                }
                foreach ($task->children as $child) {
                    $collectStakeholders($child);
                }
            };
            
            $rootTask->load(['creator', 'assignedUser', 'assignedTo', 'children.creator', 'children.assignedUser', 'children.assignedTo']);
            $collectStakeholders($rootTask);
        }
        
        // 3. Previous commenters (engagement)
        $previousCommenters = $thread->messages()
            ->where('user_id', '!=', auth()->id())
            ->with('user')
            ->get()
            ->pluck('user');
        $recipients = $recipients->merge($previousCommenters);

        // 4. Mentioned Users (NEW)
        preg_match_all('/<!--mention:(\d+)-->/', $message->content, $matches);
        if (!empty($matches[1])) {
            $mentionedUsers = User::whereIn('id', $matches[1])->get();
            $recipients = $recipients->merge($mentionedUsers);
        }

        // 5. Parent message author (Response awareness)
        if ($message->parent && $message->parent->user) {
            $recipients->push($message->parent->user);
        }
        
        // --- Final Filter and Dispatch ---
        if ($message->is_private && $thread->task) {
            $task = $thread->task;
            $recipients = $recipients->filter(function($u) use ($task) {
                return $u->id === $task->created_by_id ||
                       $u->id === $task->assigned_user_id ||
                       $task->assignedTo->contains($u->id) ||
                       $task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $u->id))->exists();
            });
        }

        $recipients = $recipients->filter(function($u) {
            return $u && $u->id !== auth()->id() && !empty($u->email);
        })->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewForumMessageNotification($message));
        }

        return back()->with('success', __('forum.message_created'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, ForumMessage $message)
    {
        $thread = $message->thread;
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        if ($thread->is_locked) {
            return back()->with('error', __('forum.thread_locked'));
        }

        if (auth()->id() !== $message->user_id && auth()->user()->getRole($team) !== 'coordinator') {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . ((int)ini_get('upload_max_filesize') * 1024),
            'drive_attachments' => 'nullable|string',
        ]);

        $message->update([
            'content' => $validated['content'],
            'is_edited' => true,
        ]);

        // Handle Local Attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $originalName = $file->getClientOriginalName();
                $datePrefix = date('Y-m-d-');
                $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

                $attachment = $message->attachments()->create([
                    'user_id' => auth()->id(),
                    'file_path' => $path,
                    'file_name' => $fileName,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => auth()->id(),
                    'action' => 'upload',
                    'metadata' => [
                        'original_name' => $originalName,
                        'size' => $file->getSize(),
                        'context' => 'edit'
                    ],
                    'ip_address' => request()->ip()
                ]);
            }
        }

        // Handle Drive/AJAX Attachments
        if ($request->has('drive_attachments') && !empty($request->drive_attachments)) {
            $driveFiles = json_decode($request->drive_attachments, true);
            if (is_array($driveFiles)) {
                foreach ($driveFiles as $file) {
                    $isLocal = isset($file['provider']) && $file['provider'] === 'local';
                    
                    $attachment = $message->attachments()->create([
                        'user_id' => auth()->id(),
                        'file_name' => $file['name'],
                        'file_path' => $isLocal ? ($file['path'] ?? '') : 'google_drive/' . $file['id'],
                        'file_size' => $file['size'] ?? 0,
                        'mime_type' => $file['mimeType'] ?? ($file['mime_type'] ?? 'application/octet-stream'),
                        'storage_provider' => $isLocal ? 'local' : 'google',
                        'provider_file_id' => $isLocal ? null : $file['id'],
                        'web_view_link' => $isLocal ? null : $file['webViewLink'],
                    ]);

                    AttachmentLog::create([
                        'attachment_id' => $attachment->id,
                        'user_id' => auth()->id(),
                        'action' => $isLocal ? 'upload' : 'drive_migration',
                        'metadata' => [
                            'file_id' => $isLocal ? null : $file['id'],
                            'source' => $isLocal ? 'local_ajax' : 'google_drive',
                            'context' => 'edit'
                        ],
                        'ip_address' => request()->ip()
                    ]);
                }
            }
        }

        return back()->with('success', __('forum.message_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, ForumMessage $message)
    {
        $thread = $message->thread;
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        if (auth()->id() !== $message->user_id && auth()->user()->getRole($team) !== 'coordinator') {
            abort(403);
        }

        $message->delete();

        return back()->with('success', __('forum.message_deleted'));
    }

    /**
     * Upload an attachment via AJAX.
     */
    public function uploadAttachment(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['message' => __('teams.unauthorized_access')], 403);
        }

        \Illuminate\Support\Facades\Log::info('Forum Upload Request ALL:', $request->all());
        
        $file = $request->file('attachment_file');
        
        if (!$file) {
            \Illuminate\Support\Facades\Log::error('No file found in request with name: attachment_file');
            return response()->json(['message' => 'No se encontró el archivo en la petición.'], 422);
        }

        if (!$file->isValid()) {
            $errorCode = $file->getError();
            $errorMessage = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE => 'El archivo es demasiado grande para la configuración del servidor (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande para el formulario.',
                UPLOAD_ERR_PARTIAL => 'La subida se ha interrumpido.',
                UPLOAD_ERR_NO_FILE => 'No se ha subido ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
                default => 'Error de subida desconocido: ' . $file->getErrorMessage(),
            };
            
            \Illuminate\Support\Facades\Log::error('File Upload Error:', ['code' => $errorCode, 'msg' => $errorMessage]);
            return response()->json(['message' => 'Error de subida: ' . $errorMessage], 422);
        }

        \Illuminate\Support\Facades\Log::info('File Received:', [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize() . ' bytes',
            'mime' => $file->getMimeType()
        ]);

        $originalName = $file->getClientOriginalName();
        $path = $file->store('forum/attachments', 'public');

        return response()->json([
            'name' => $originalName,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Upload an image from the Markdown editor via AJAX paste/drag&drop.
     */
    public function uploadImage(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['message' => __('teams.unauthorized_access')], 403);
        }

        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        $path = $request->file('image')->store('forum', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }

        public function replaceInlineImage(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['message' => __('teams.unauthorized_access')], 403);
        }

        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        
        // Extract the path from the URL
        $parsedUrl = parse_url($path, PHP_URL_PATH);
        
        // Ensure path contains /storage/forum/ to prevent Path Traversal
        if (!$parsedUrl || !str_contains($parsedUrl, '/storage/forum/')) {
            return response()->json(['message' => 'Ruta de imagen inválida'], 400);
        }
        
        // Convert public URL path to storage relative path
        // e.g. /storage/forum/file.png -> forum/file.png
        $relativePath = substr($parsedUrl, strpos($parsedUrl, '/storage/') + 9);
        
        // Ensure file exists in the public disk
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
            return response()->json(['message' => 'Imagen original no encontrada'], 404);
        }

        // Overwrite the file
        $request->file('image')->storeAs(
            dirname($relativePath),
            basename($relativePath),
            'public'
        );

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $relativePath) . '?t=' . time(),
        ]);
    }

/**
     * Alternar el voto (votar/desvotar) en un mensaje del foro.
     */
    public function voteToggle(Team $team, ForumMessage $message)
    {
        $thread = $message->thread;
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        $user = auth()->user();
        
        if ($message->votes()->where('user_id', $user->id)->exists()) {
            $message->votes()->detach($user->id);
            $voted = false;
        } else {
            $message->votes()->attach($user->id);
            $voted = true;
        }
        if (request()->ajax() || request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'voted' => $voted,
                'votes_count' => $message->votes()->count()
            ]);
        }
        return back()->with('success', $voted ? 'Voto registrado correctamente.' : 'Voto retirado correctamente.');
    }
}
