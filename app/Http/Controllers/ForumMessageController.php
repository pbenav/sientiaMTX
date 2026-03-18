<?php

namespace App\Http\Controllers;

use App\Models\ForumMessage;
use App\Models\ForumThread;
use App\Models\Team;

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

        $thread->messages()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        // Touch the thread to push it to the top
        $thread->touch();

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
