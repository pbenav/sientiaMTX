<?php

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
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . ((int)ini_get('upload_max_filesize') * 1024),
            'drive_attachments' => 'nullable|string',
            'is_private' => 'nullable|boolean',
        ]);

        $message = $thread->messages()->create([
            'user_id' => auth()->id(),
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

        // Handle Drive Attachments
        if ($request->has('drive_attachments') && !empty($request->drive_attachments)) {
            $driveFiles = json_decode($request->drive_attachments, true);
            if (is_array($driveFiles)) {
                foreach ($driveFiles as $file) {
                    $attachment = $message->attachments()->create([
                        'user_id' => auth()->id(),
                        'file_name' => $file['name'],
                        'file_path' => 'google_drive/' . $file['id'],
                        'file_size' => $file['size'] ?? 0,
                        'mime_type' => $file['mimeType'] ?? 'application/octet-stream',
                        'storage_provider' => 'google',
                        'provider_file_id' => $file['id'],
                        'web_view_link' => $file['webViewLink'],
                    ]);

                    AttachmentLog::create([
                        'attachment_id' => $attachment->id,
                        'user_id' => auth()->id(),
                        'action' => 'drive_migration',
                        'metadata' => [
                            'file_id' => $file['id'],
                            'source' => 'google_drive'
                        ],
                        'ip_address' => request()->ip()
                    ]);
                }
            }
        }

        // Touch the thread to push it to the top
        $thread->touch();

        // --- Multi-Stakeholder Notification Logic ---
        $recipients = collect();
        
        // 1. Thread creator (always notified of new activity)
        if ($thread->user) {
            $recipients->push($thread->user);
        }
        
        // 2. Task-related stakeholders
        if ($thread->task) {
            // Find ALL tasks in this hierarchy (up to the root and down to all descendants)
            // Since subtasks share the root thread, stakeholders from ANY subtask should know.
            
            // Get root task first
            $rootTask = $thread->task;
            while ($rootTask->parent_id && $rootTask->parent) {
                $rootTask = $rootTask->parent;
            }
            
            // Recurse function to collect all involved users in the task tree
            $collectStakeholders = function($task) use (&$recipients, &$collectStakeholders) {
                // Owner
                if ($task->creator) $recipients->push($task->creator);
                // Direct Assignee (Individual Instance)
                if ($task->assignedUser) $recipients->push($task->assignedUser);
                // Collaborators (Template Task)
                if ($task->assignedTo) {
                    foreach ($task->assignedTo as $user) {
                        $recipients->push($user);
                    }
                }
                
                // Recurse to children
                foreach ($task->children as $child) {
                    $collectStakeholders($child);
                }
            };
            
            // We start from the root task and go down, as all tasks in this tree use this thread
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
        
        // --- Final Filter and Dispatch ---
        // If private, only stakeholders receive it. If public, everyone in previous list.
        if ($message->is_private && $thread->task) {
            // Re-filter recipients: only those who have access to the task
            $task = $thread->task;
            $recipients = $recipients->filter(function($u) use ($task) {
                return $u->id === $task->created_by_id ||
                       $u->id === $task->assigned_user_id ||
                       $task->assignedTo->contains($u->id) ||
                       $task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $u->id))->exists();
            });
        }

        // Clean up: Unique users, exclude current sender, filter empty values
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
        ]);

        $message->update([
            'content' => $validated['content'],
            'is_edited' => true,
        ]);

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
     * Upload an image from the Markdown editor via AJAX paste/drag&drop.
     */
    public function uploadImage(Request $request, Team $team)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        $path = $request->file('image')->store('forum', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
