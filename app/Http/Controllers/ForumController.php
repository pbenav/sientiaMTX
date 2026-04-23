<?php

namespace App\Http\Controllers;

use App\Models\ForumThread;
use App\Models\Task;
use App\Models\Team;
use App\Notifications\NewForumMessageNotification;
use App\Models\AttachmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ForumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $userId = auth()->id();
        $isCoordinator = $team->isCoordinator(auth()->user());

        $search = $request->query('search');

        $threads = $team->forumThreads()
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereHas('messages', function($mq) use ($search) {
                          $mq->where('content', 'like', "%{$search}%");
                      });
                });
            })
            ->with(['user', 'task', 'messages' => function ($query) {
                // Get the latest message for each thread
                $query->latest()->limit(1);
            }])
            ->where(function($query) use ($userId, $isCoordinator) {
                if ($isCoordinator) {
                    return $query; // Coordinators see everything in the team
                }
                
                $query->whereNull('task_id')
                      ->orWhereHas('task', function($q) use ($userId) {
                          $q->where('visibility', 'public')
                            ->orWhere('created_by_id', $userId)
                            ->orWhere('assigned_user_id', $userId)
                            ->orWhereHas('assignedTo', function($q2) use ($userId) {
                                $q2->where('users.id', $userId);
                            })
                            ->orWhereHas('assignedGroups.users', function($q3) use ($userId) {
                                $q3->where('users.id', $userId);
                            });
                      });
            })
            ->withCount('messages')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('teams.forum.index', compact('team', 'threads'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'task_id' => 'nullable|exists:tasks,id',
            'content' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . ((int)ini_get('upload_max_filesize') * 1024),
            'drive_attachments' => 'nullable|string',
        ]);

        return \DB::transaction(function() use ($validated, $team, $request) {
            $task = null;
            if ($validated['task_id']) {
                $task = Task::where('team_id', $team->id)->findOrFail($validated['task_id']);
                
                // Privacy check for the task before linking a thread
                $isCoordinator = $team->isCoordinator(auth()->user());
                $userId = auth()->id();
                if (!$isCoordinator) {
                    $hasAccess = $task->visibility === 'public' ||
                                 $task->created_by_id === $userId ||
                                 $task->assigned_user_id === $userId ||
                                 $task->assignedTo->contains($userId) ||
                                 $task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $userId))->exists();
                    
                    if (!$hasAccess) {
                        abort(403, 'No puedes crear hilos para tareas privadas a las que no tienes acceso.');
                    }
                }
                
                // Force link to the root task
                while ($task->parent_id && $task->parent) {
                    $task = $task->parent;
                }

                // If the root task already has a thread, append the message instead of creating a new one
                if ($task->forumThread) {
                    $thread = $task->forumThread;
                    $message = $thread->messages()->create([
                        'user_id' => auth()->id(),
                        'content' => $validated['content'],
                    ]);
                    
                    $thread->touch();
                    $this->notifyThreadParticipants($thread, $message);

                    return redirect()->route('teams.forum.show', [$team, $thread])
                        ->with('success', __('forum.thread_already_existed') ?? 'Esta tarea principal ya tenía un hilo activo; el mensaje se ha añadido allí.');
                }
            }

            $thread = $team->forumThreads()->create([
                'title' => $validated['title'],
                'user_id' => auth()->id(),
                'task_id' => $task ? $task->id : null,
            ]);

            $message = $thread->messages()->create([
                'user_id' => auth()->id(),
                'content' => $validated['content'],
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

            $this->notifyThreadParticipants($thread, $message);

            return redirect()->route('teams.forum.show', [$team, $thread])
                ->with('success', __('forum.thread_created'));
        });
    }

    /**
     * Internal helper to notify participants of a new message.
     */
    protected function notifyThreadParticipants($thread, $message)
    {
        $recipients = collect();
        
        // 1. Thread creator
        if ($thread->user) $recipients->push($thread->user);
        
        // 2. Task owner/assignees if associated
        if ($thread->task) {
            $recipients->push($thread->task->creator);
            if ($thread->task->assignedUser) {
                $recipients->push($thread->task->assignedUser);
            }
            $recipients = $recipients->merge($thread->task->assignedTo);
        }
        
        // 3. Other people who commented
        $previousCommenters = $thread->messages()
            ->where('user_id', '!=', auth()->id())
            ->with('user')
            ->get()
            ->pluck('user');
        $recipients = $recipients->merge($previousCommenters);

        // Filter: Unique users, exclude current sender, and must have an email
        $recipients = $recipients->filter(function($u) {
            return $u && $u->id !== auth()->id() && !empty($u->email);
        })->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewForumMessageNotification($message));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team, ForumThread $thread)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        if ($thread->team_id !== $team->id) {
            return redirect()->route('teams.forum.index', $team)->with('warning', __('Hilo no encontrado en este equipo.'));
        }

        // Privacy Check: If the thread is linked to a task, follow task visibility rules
        if ($thread->task_id) {
            $task = $thread->task;
            $userId = auth()->id();
            $isCoordinator = $team->isCoordinator(auth()->user());

            if (!$isCoordinator) {
                $hasAccess = $task->visibility === 'public' ||
                             $task->created_by_id === $userId ||
                             $task->assigned_user_id === $userId ||
                             $task->assignedTo->contains($userId) ||
                             $task->assignedGroups()->whereHas('users', fn($q) => $q->where('users.id', $userId))->exists();

                if (!$hasAccess) {
                    return redirect()->route('teams.forum.index', $team)
                        ->with('warning', __('tasks.unauthorized_access') ?? 'No tienes permiso para ver este hilo privado.');
                }
            }
        }

        $thread->load(['user', 'task']);
        $messages = $thread->messages()->with('user')->oldest()->paginate(20);

        return view('teams.forum.show', compact('team', 'thread', 'messages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, ForumThread $thread)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }
        if ($thread->team_id !== $team->id) {
            return redirect()->route('teams.forum.index', $team)->with('warning', __('Hilo no encontrado en este equipo.'));
        }

        // Only author or coordinator can update the thread itself (e.g., pin, lock, title)
        // Adjust abilities later with a Policy
        if (auth()->id() !== $thread->user_id && auth()->user()->getRole($team) !== 'coordinator') {
            return back()->with('warning', __('No tienes permisos para modificar este hilo.'));
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'is_pinned' => 'sometimes|boolean',
            'is_locked' => 'sometimes|boolean',
        ]);

        $thread->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'title' => $thread->title]);
        }

        return back()->with('success', __('forum.thread_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, ForumThread $thread)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }
        if ($thread->team_id !== $team->id) {
            return redirect()->route('teams.forum.index', $team)->with('warning', __('Hilo no encontrado en este equipo.'));
        }

        if (auth()->id() !== $thread->user_id && auth()->user()->getRole($team) !== 'coordinator') {
            return back()->with('warning', __('No tienes permisos para eliminar este hilo.'));
        }

        $thread->delete();

        return redirect()->route('teams.forum.index', $team)
            ->with('success', __('forum.thread_deleted'));
    }
}
