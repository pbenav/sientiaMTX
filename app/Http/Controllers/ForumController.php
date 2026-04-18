<?php

namespace App\Http\Controllers;

use App\Models\ForumThread;
use App\Models\Task;
use App\Models\Team;
use App\Notifications\NewForumMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ForumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $userId = auth()->id();
        $isCoordinator = $team->isCoordinator(auth()->user());

        $threads = $team->forumThreads()
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
            ->paginate(15);

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
        ]);

        $task = null;
        if ($validated['task_id']) {
            $task = Task::where('team_id', $team->id)->findOrFail($validated['task_id']);
            
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

        $this->notifyThreadParticipants($thread, $message);

        return redirect()->route('teams.forum.show', [$team, $thread])
            ->with('success', __('forum.thread_created'));
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
            abort(404);
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
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        // Only author or coordinator can update the thread itself (e.g., pin, lock, title)
        // Adjust abilities later with a Policy
        if (auth()->id() !== $thread->user_id && auth()->user()->getRole($team) !== 'coordinator') {
            abort(403);
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
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        if ($thread->team_id !== $team->id) {
            abort(404);
        }

        if (auth()->id() !== $thread->user_id && auth()->user()->getRole($team) !== 'coordinator') {
            abort(403);
        }

        $thread->delete();

        return redirect()->route('teams.forum.index', $team)
            ->with('success', __('forum.thread_deleted'));
    }
}
