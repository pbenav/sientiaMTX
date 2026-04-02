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
}
