<?php

namespace App\Http\Controllers;

use App\Models\TelegramMessage;
use App\Models\TaskAttachment;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StorageController extends Controller
{
    /**
     * Mostrar estadísticas de uso de disco.
     */
    public function index(Team $team)
    {
        // Solo coordinadores o managers pueden ver esto
        if (!auth()->user()->is_admin && !$team->isCoordinator(auth()->user())) {
            abort(403);
        }

        // Sync real disk usage for this team before displaying
        $team->syncDiskUsed();
        $team->refresh();

        // Telegram media scoped to this team
        $telegramMessages = TelegramMessage::where('team_id', $team->id)
            ->whereNotNull('photo_path')
            ->orWhere(function($q) use ($team) {
                $q->where('team_id', $team->id)->whereNotNull('voice_path');
            })
            ->orWhere(function($q) use ($team) {
                $q->where('team_id', $team->id)->whereNotNull('sticker_path');
            })
            ->get();

        $telegramBytes = 0;
        $telegramCount = 0;
        foreach ($telegramMessages as $msg) {
            $path = $msg->photo_path ?? $msg->voice_path ?? $msg->sticker_path;
            if ($path && Storage::disk('public')->exists($path)) {
                $telegramBytes += Storage::disk('public')->size($path);
                $telegramCount++;
            }
        }

        // Task attachments scoped to this team
        $teamTaskIds = $team->tasks()->pluck('id');
        $attachments = TaskAttachment::whereIn('attachable_id', $teamTaskIds)
            ->where('attachable_type', \App\Models\Task::class)
            ->where('storage_provider', '!=', 'google')
            ->get();

        $attachmentBytes = 0;
        foreach ($attachments as $att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                $attachmentBytes += Storage::disk('public')->size($att->file_path);
            }
        }

        $totalBytes = $telegramBytes + $attachmentBytes;

        $stats = [
            'telegram' => [
                'count' => $telegramCount,
                'size'  => $telegramBytes,
                'readable_size' => $this->formatBytes($telegramBytes),
            ],
            'attachments' => [
                'count' => $attachments->count(),
                'size'  => $attachmentBytes,
                'readable_size' => $this->formatBytes($attachmentBytes),
            ],
            'total_size' => [
                'size' => $totalBytes,
                'readable_size' => $this->formatBytes($totalBytes),
            ],
        ];

        return view('teams.storage.index', compact('team', 'stats'));
    }

    /**
     * Purgar archivos antiguos.
     */
    public function purge(Request $request, Team $team)
    {
        $user = auth()->user();
        if (!$user->is_admin && !$team->isCoordinator($user)) {
            abort(403);
        }

        $request->validate([
            'days' => 'required|integer|min:1',
            'types' => 'required|array', // ['telegram', 'attachments']
        ]);

        $days = $request->days;
        $dateLimit = Carbon::now()->subDays($days);
        $deletedCount = 0;
        $freedSpace = 0;

        if (in_array('telegram', $request->types)) {
            $messages = TelegramMessage::where('team_id', $team->id)
                ->where('created_at', '<', $dateLimit)
                ->where(function($q) {
                    $q->whereNotNull('photo_path')
                      ->orWhereNotNull('voice_path')
                      ->orWhereNotNull('sticker_path');
                })->get();

            foreach ($messages as $msg) {
                if ($msg->photo_path) $freedSpace += $this->deleteFile($msg->photo_path);
                if ($msg->voice_path) $freedSpace += $this->deleteFile($msg->voice_path);
                if ($msg->sticker_path) $freedSpace += $this->deleteFile($msg->sticker_path);
                
                // Opcional: No borramos el registro del mensaje, solo los archivos para ahorrar espacio
                // Pero si el usuario lo prefiere, podemos borrar el registro. Por ahora limpiamos rutas.
                $msg->update([
                    'photo_path' => null,
                    'voice_path' => null,
                    'sticker_path' => null,
                    'text' => $msg->text . "\n\n*(Archivo purgado por mantenimiento)*"
                ]);
                $deletedCount++;
            }
            
            // SECURITY: Also purge physical files that might have been orphaned or are duplicates
            // based on the same date logic, even if not in DB? 
            // Better stick to DB records to avoid deleting shared assets.
        }

        if (in_array('attachments', $request->types)) {
            // Adjuntos de Tareas
            $taskAttachments = TaskAttachment::where('attachable_type', Task::class)
                ->whereHas('task', fn($q) => $q->where('team_id', $team->id))
                ->where('created_at', '<', $dateLimit)
                ->get();

            // Adjuntos de Mensajes de Foro
            $forumAttachments = TaskAttachment::where('attachable_type', \App\Models\ForumMessage::class)
                ->whereHas('attachable', function($q) use ($team) {
                    $q->whereHas('thread', function($sq) use ($team) {
                        $sq->where('team_id', $team->id);
                    });
                })
                ->where('created_at', '<', $dateLimit)
                ->get();

            $allAttachments = $taskAttachments->concat($forumAttachments);

            foreach ($allAttachments as $att) {
                $freedSpace += $this->deleteFile($att->file_path);
                $att->delete();
                $deletedCount++;
            }
        }

        $formattedSpace = $this->formatBytes($freedSpace);

        return back()->with('success', "Limpieza completada: Se han eliminado $deletedCount archivos y liberado $formattedSpace.");
    }

    protected function getFolderStats($folder)
    {
        $files = Storage::disk('public')->allFiles($folder);
        $size = 0;
        foreach ($files as $file) {
            $size += Storage::disk('public')->size($file);
        }

        return [
            'count' => count($files),
            'size' => $size,
            'readable_size' => $this->formatBytes($size)
        ];
    }

    protected function deleteFile($path)
    {
        if (!$path) return 0;
        
        $size = 0;
        if (Storage::disk('public')->exists($path)) {
            $size = Storage::disk('public')->size($path);
            Storage::disk('public')->delete($path);
        }
        return $size;
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
