<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityNote;
use App\Models\Team;
use Illuminate\Http\Request;

class ActivityNoteController extends Controller
{
    public function addNote(Request $request, Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('addNote', $activity)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'visibility' => 'required|in:private,internal',
        ]);

        ActivityNote::create([
            'activity_id' => $activity->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', __('activities.note_added'));
    }

    /**
     * Actualiza una nota/comentario.
     */
    public function updateNote(Request $request, Team $team, Activity $activity, ActivityNote $note)
    {
        if ($note->user_id !== auth()->id() && auth()->user()->cannot('delete', $activity)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'visibility' => 'required|in:private,internal',
        ]);

        $note->update([
            'content' => $validated['content'],
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', __('activities.note_updated'));
    }

    /**
     * Update or create a private note for an activity.
     */
    public function updatePrivateNote(Request $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            abort(404);
        }

        if ($request->user()->cannot('view', $activity)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'nullable|string',
        ]);

        \App\Models\TaskPrivateNote::updateOrCreate(
            ['task_id' => $activity->id, 'user_id' => auth()->id()],
            ['content' => $validated['content'] ?? '']
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Nota guardada correctamente.']);
        }

        return back()->with('success', 'Nota privada guardada correctamente.');
    }

    /**
     * Elimina una nota/comentario.
     */
    public function deleteNote(Team $team, Activity $activity, ActivityNote $note)
    {
        if ($note->user_id !== auth()->id() && auth()->user()->cannot('delete', $activity)) {
            abort(403);
        }

        $note->delete();

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', __('activities.note_deleted'));
    }

    /**
     * Sube adjuntos.
     */

}
