<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Display a listing of the user's uploaded files.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $teamId = $request->query('team_id');
        
        $query = TaskAttachment::where('user_id', $user->id)
            ->with(['task.team'])
            ->orderBy('created_at', 'desc');

        if ($teamId) {
            $query->whereHasMorph('attachable', [Task::class], function($q) use ($teamId) {
                $q->where('team_id', $teamId);
            });
        }

        $attachments = $query->get();
        $teams = $user->teams;

        return view('media.index', compact('attachments', 'user', 'teams', 'teamId'));
    }

    /**
     * Download the attachment.
     */
    public function download(TaskAttachment $attachment)
    {
        if ($attachment->user_id !== auth()->id()) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo físico no se encuentra en el servidor. Contacte con el administrador.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(TaskAttachment $attachment)
    {
        // Security check: only the owner can delete their files from here
        if ($attachment->user_id !== auth()->id()) {
            abort(403);
        }

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
            
            // Update user disk usage
            $attachment->user->decrement('disk_used', $attachment->file_size);
        }
        
        $attachment->delete();

        return back()->with('success', __('tasks.deleted'));
    }
}
