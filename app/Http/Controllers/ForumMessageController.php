<?php

namespace App\Http\Controllers;

use App\Models\ForumMessage;
use App\Models\ForumThread;
use App\Models\Team;
use App\Models\User;
use App\Notifications\NewForumMessageNotification;
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
        ]);

        $message = $thread->messages()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        // Touch the thread to push it to the top
        $thread->touch();

        // --- Notification Logic ---
        $recipients = collect();
        
        // 1. Thread creator
        $recipients->push($thread->user);
        
        // 2. Task owner/assignees if associated
        if ($thread->task) {
            $recipients->push($thread->task->creator);
            if ($thread->task->assignedUser) {
                $recipients->push($thread->task->assignedUser);
            }
            $recipients = $recipients->merge($thread->task->assignedTo);
        }
        
        // 3. Other people who commented (optional but good for engagement)
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
}
