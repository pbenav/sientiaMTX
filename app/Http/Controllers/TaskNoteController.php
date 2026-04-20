<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use App\Models\TaskPrivateNote;
use Illuminate\Http\Request;

class TaskNoteController extends Controller
{
    /**
     * Update or create a private note for a task.
     */
    public function update(Request $request, Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            abort(404);
        }

        // Authorization: Any user who can view the task can have private notes for it
        if ($request->user()->cannot('view', $task)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'nullable|string',
        ]);

        \Log::info("Saving Private Note for task#{$task->id} user#" . auth()->id(), ['content' => $validated['content']]);

        try {
            $note = TaskPrivateNote::updateOrCreate(
                ['task_id' => $task->id, 'user_id' => auth()->id()],
                ['content' => $validated['content'] ?? '']
            );
            
            \Log::info("Note saved successfully ID: " . $note->id);
        } catch (\Exception $e) {
            \Log::error("Error saving private note: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error en la base de datos.'], 500);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Nota guardada correctamente.']);
        }

        return back()->with('success', 'Nota privada guardada correctamente.');
    }
}
