<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\ActivityAttachment;
use App\Models\Activity;
use App\Models\TaskAttachment;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    /**
     * Display a listing of the user's uploaded files (ActivityAttachments y legacy TaskAttachments).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $teamId = $request->query('team_id');
        $user->syncDiskUsed();
        $user->refresh();

        // 1. ActivityAttachments
        $actQuery = ActivityAttachment::with(['activity.team'])
            ->where(function($q) use ($user) {
                if (!$user->is_admin) {
                    $q->where('uploaded_by_id', $user->id);
                }
            })
            ->latest();

        if ($teamId) {
            $actQuery->whereHas('activity', fn($q) => $q->where('team_id', $teamId));
        }

        $activityAttachments = $actQuery->get()->map(function($att) {
            return (object) [
                'id' => $att->id,
                'source_type' => 'activity',
                'file_name' => $att->file_name,
                'file_path' => $att->file_path,
                'file_size' => $att->file_size ?: 0,
                'mime_type' => $att->mime_type ?: 'application/octet-stream',
                'created_at' => $att->created_at,
                'activity' => $att->activity,
                'activity_title' => $att->activity?->title,
                'activity_id' => $att->activity_id,
                'team_id' => $att->activity?->team_id,
                'team_name' => $att->activity?->team?->name,
                'is_deleted' => $att->activity?->trashed() ?? false,
                'storage_provider' => $att->disk ?: 'public',
                'web_view_link' => null,
            ];
        });

        // 2. Legacy TaskAttachments (que no estén ya en activity_attachments por file_path)
        $existingPaths = $activityAttachments->pluck('file_path')->filter()->all();
        $taskQuery = TaskAttachment::with(['task.team'])
            ->where(function($q) use ($user) {
                if (!$user->is_admin) {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest();

        if ($teamId) {
            $taskQuery->whereHasMorph('attachable', [Task::class], function($q) use ($teamId) {
                $q->where('team_id', $teamId);
            });
        }

        $taskAttachments = $taskQuery->get()
            ->filter(fn($ta) => !in_array($ta->file_path, $existingPaths))
            ->map(function($ta) {
                $activity = null;
                $activityTitle = null;
                $activityId = null;
                $teamId = null;
                $teamName = null;
                $isDeleted = false;

                if ($ta->attachable_type === Task::class) {
                    // Buscar mediante mapping a Actividad
                    $mapping = DB::table('activity_task_mapping')->where('task_id', $ta->attachable_id)->first();
                    if ($mapping) {
                        $activity = Activity::withTrashed()->with('team')->find($mapping->activity_id);
                    }
                    if (!$activity) {
                        $task = Task::withTrashed()->with('team')->find($ta->attachable_id);
                        if ($task) {
                            $activityTitle = $task->title;
                            $teamId = $task->team_id;
                            $teamName = $task->team?->name;
                            $isDeleted = $task->trashed();
                        }
                    } else {
                        $activityTitle = $activity->title;
                        $activityId = $activity->id;
                        $teamId = $activity->team_id;
                        $teamName = $activity->team?->name;
                        $isDeleted = $activity->trashed();
                    }
                } elseif ($ta->task) {
                    $activityTitle = $ta->task->title;
                    $teamId = $ta->task->team_id;
                    $teamName = $ta->task->team?->name;
                    $isDeleted = $ta->task->trashed();
                }

                return (object) [
                    'id' => $ta->id,
                    'source_type' => 'task',
                    'file_name' => $ta->file_name,
                    'file_path' => $ta->file_path,
                    'file_size' => $ta->file_size ?: 0,
                    'mime_type' => $ta->mime_type ?: 'application/octet-stream',
                    'created_at' => $ta->created_at,
                    'activity' => $activity,
                    'activity_title' => $activityTitle,
                    'activity_id' => $activityId,
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'is_deleted' => $isDeleted,
                    'storage_provider' => $ta->storage_provider ?: 'public',
                    'web_view_link' => $ta->web_view_link,
                ];
            });

        $attachments = $activityAttachments->concat($taskAttachments)->sortByDesc('created_at')->values();
        $teams = $user->teams;

        // Calcular archivos huérfanos / obsoletos disponibles para purgar
        $orphanStats = $this->calculateOrphanStats();

        return view('media.index', compact('attachments', 'user', 'teams', 'teamId', 'orphanStats'));
    }

    /**
     * Download an attachment (supports both ActivityAttachment and TaskAttachment).
     */
    public function download(Request $request, $id)
    {
        $type = $request->query('type', 'activity');
        
        if ($type === 'task') {
            $attachment = TaskAttachment::findOrFail($id);
            if ($attachment->user_id !== auth()->id() && !auth()->user()->is_admin) {
                abort(403);
            }
            $filePath = $attachment->file_path;
            $fileName = $attachment->file_name;
        } else {
            $attachment = ActivityAttachment::findOrFail($id);
            if ($attachment->uploaded_by_id !== auth()->id() && !auth()->user()->is_admin) {
                abort(403);
            }
            $filePath = $attachment->file_path;
            $fileName = $attachment->file_name;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            return back()->with('error', 'El archivo físico no se encuentra en el servidor. Contacte con el administrador.');
        }

        $fullPath = $disk->path($filePath);
        if (!str_starts_with(realpath($fullPath), realpath($disk->path('')))) {
            abort(403);
        }

        return $disk->download($filePath, $fileName);
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->input('source_type', 'activity');
        $user = auth()->user();

        if ($type === 'task') {
            $attachment = TaskAttachment::findOrFail($id);
            if ($attachment->user_id !== $user->id && !$user->is_admin) {
                abort(403);
            }
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        } else {
            $attachment = ActivityAttachment::findOrFail($id);
            if ($attachment->uploaded_by_id !== $user->id && !$user->is_admin) {
                abort(403);
            }
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }

        $user->syncDiskUsed();

        return back()->with('success', __('tasks.deleted'));
    }

    /**
     * Purgar archivos obsoletos y huérfanos (tanto registros de tareas/actividades eliminadas como archivos fantasma en disco).
     */
    public function purgeOrphans(Request $request)
    {
        $user = auth()->user();
        if (!$user->is_admin) {
            abort(403, 'Solo administradores pueden realizar una purga global.');
        }

        $deletedRecordsCount = 0;
        $deletedPhysicalCount = 0;
        $bytesFreed = 0;

        // 1. Purgar TaskAttachments de tareas eliminadas o inexistentes
        $taskAtts = TaskAttachment::where('attachable_type', Task::class)->get();
        foreach ($taskAtts as $ta) {
            $task = Task::withTrashed()->find($ta->attachable_id);
            if (!$task || $task->trashed()) {
                if ($ta->file_path && Storage::disk('public')->exists($ta->file_path)) {
                    $bytesFreed += Storage::disk('public')->size($ta->file_path);
                    Storage::disk('public')->delete($ta->file_path);
                }
                $ta->delete();
                $deletedRecordsCount++;
            }
        }

        // 2. Purgar ActivityAttachments de actividades eliminadas o inexistentes
        $actAtts = ActivityAttachment::all();
        foreach ($actAtts as $aa) {
            $activity = Activity::withTrashed()->find($aa->activity_id);
            if (!$activity || $activity->trashed()) {
                if ($aa->file_path && Storage::disk('public')->exists($aa->file_path)) {
                    $bytesFreed += Storage::disk('public')->size($aa->file_path);
                    Storage::disk('public')->delete($aa->file_path);
                }
                $aa->delete();
                $deletedRecordsCount++;
            }
        }

        // 3. Purgar archivos físicos huérfanos en disco (fantasmas sin registro en la BD)
        $allDbFiles = $this->getAllReferencedFiles();
        $physicalFiles = collect(Storage::disk('public')->allFiles());

        foreach ($physicalFiles as $file) {
            if ($this->isProtectedFile($file)) {
                continue;
            }

            if (!$allDbFiles->contains($file)) {
                if (Storage::disk('public')->exists($file)) {
                    $bytesFreed += Storage::disk('public')->size($file);
                    Storage::disk('public')->delete($file);
                    $deletedPhysicalCount++;
                }
            }
        }

        // Sincronizar cuotas de todos los equipos y usuarios
        foreach (Team::all() as $t) {
            $t->syncDiskUsed();
        }
        $user->syncDiskUsed();

        $mbFreed = round($bytesFreed / 1024 / 1024, 2);

        return back()->with('success', "Purga completada con éxito: Se eliminaron {$deletedRecordsCount} registros obsoletos y {$deletedPhysicalCount} archivos fantasma en disco, liberando {$mbFreed} MB.");
    }

    /**
     * Calcula estadísticas de archivos huérfanos y fantasmas.
     */
    protected function calculateOrphanStats(): array
    {
        $deletedRecords = 0;
        $deletedRecordsBytes = 0;

        // TaskAttachments de tareas eliminadas
        $taskAtts = TaskAttachment::where('attachable_type', Task::class)->get();
        foreach ($taskAtts as $ta) {
            $task = Task::withTrashed()->find($ta->attachable_id);
            if (!$task || $task->trashed()) {
                $deletedRecords++;
                if ($ta->file_path && Storage::disk('public')->exists($ta->file_path)) {
                    $deletedRecordsBytes += Storage::disk('public')->size($ta->file_path);
                }
            }
        }

        // ActivityAttachments de actividades eliminadas
        $actAtts = ActivityAttachment::all();
        foreach ($actAtts as $aa) {
            $activity = Activity::withTrashed()->find($aa->activity_id);
            if (!$activity || $activity->trashed()) {
                $deletedRecords++;
                if ($aa->file_path && Storage::disk('public')->exists($aa->file_path)) {
                    $deletedRecordsBytes += Storage::disk('public')->size($aa->file_path);
                }
            }
        }

        // Archivos fantasma en disco
        $allDbFiles = $this->getAllReferencedFiles();
        $physicalFiles = collect(Storage::disk('public')->allFiles());

        $ghostFilesCount = 0;
        $ghostFilesBytes = 0;

        foreach ($physicalFiles as $file) {
            if ($this->isProtectedFile($file)) {
                continue;
            }

            if (!$allDbFiles->contains($file)) {
                $ghostFilesCount++;
                $ghostFilesBytes += Storage::disk('public')->size($file);
            }
        }

        $totalBytes = $deletedRecordsBytes + $ghostFilesBytes;

        return [
            'obsolete_records' => $deletedRecords,
            'ghost_files' => $ghostFilesCount,
            'total_files' => $deletedRecords + $ghostFilesCount,
            'total_mb' => round($totalBytes / 1024 / 1024, 2),
        ];
    }

    /**
     * Obtiene la colección completa de rutas de archivos registrados en la base de datos.
     */
    protected function getAllReferencedFiles()
    {
        $files = collect();
        $files = $files->concat(TaskAttachment::pluck('file_path'));
        $files = $files->concat(ActivityAttachment::pluck('file_path'));
        $files = $files->concat(DB::table('chat_messages')->whereNotNull('file_path')->pluck('file_path'));
        $files = $files->concat(DB::table('users')->whereNotNull('profile_photo_path')->pluck('profile_photo_path'));
        $files = $files->concat(DB::table('telegram_messages')->whereNotNull('photo_path')->pluck('photo_path'));
        $files = $files->concat(DB::table('telegram_messages')->whereNotNull('voice_path')->pluck('voice_path'));
        $files = $files->concat(DB::table('telegram_messages')->whereNotNull('sticker_path')->pluck('sticker_path'));
        $files = $files->concat(DB::table('whatsapp_messages')->whereNotNull('photo_path')->pluck('photo_path'));
        $files = $files->concat(DB::table('whatsapp_messages')->whereNotNull('voice_path')->pluck('voice_path'));
        $files = $files->concat(DB::table('whatsapp_messages')->whereNotNull('sticker_path')->pluck('sticker_path'));
        $files = $files->concat(DB::table('whatsapp_messages')->whereNotNull('animation_path')->pluck('animation_path'));

        // Adjuntos en JSON de quick_notes
        $notes = DB::table('quick_notes')->whereNotNull('attachments')->pluck('attachments');
        foreach ($notes as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $att) {
                    if (!empty($att['path'])) {
                        $files->push($att['path']);
                    }
                }
            }
        }

        return $files->filter()->unique()->values();
    }

    /**
     * Comprueba si un archivo en disco del storage no debe ser purgado (iconos, avatares base, etc.).
     */
    protected function isProtectedFile(string $file): bool
    {
        $protectedPrefixes = [
            'avatars/',
            'defaults/',
            'icons/',
            'favicons/',
        ];

        foreach ($protectedPrefixes as $prefix) {
            if (str_starts_with($file, $prefix)) {
                return true;
            }
        }

        return str_ends_with($file, '.gitignore');
    }
}
