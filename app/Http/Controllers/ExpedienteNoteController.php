<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Team;
use App\Models\Expediente;
use App\Models\ExpedienteNote;

class ExpedienteNoteController extends Controller
{
    public function store(Request $request, Team $team, Expediente $expediente)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'is_private' => 'boolean',
        ]);

        $expediente->notes()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'is_private' => $request->has('is_private') ? $validated['is_private'] : false,
        ]);

        return back()->with('success', 'Nota añadida correctamente.');
    }

    public function update(Request $request, Team $team, Expediente $expediente, ExpedienteNote $note)
    {
        if ($note->is_private && $note->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar esta nota privada.');
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'is_private' => 'boolean',
        ]);

        $note->update([
            'content' => $validated['content'],
            'is_private' => $request->has('is_private') ? $validated['is_private'] : false,
        ]);

        return back()->with('success', 'Nota actualizada correctamente.');
    }

    public function destroy(Team $team, Expediente $expediente, ExpedienteNote $note)
    {
        if ($note->user_id !== auth()->id() && !auth()->user()->isCoordinator($team)) {
            abort(403);
        }

        $note->delete();

        return back()->with('success', 'Nota eliminada correctamente.');
    }
}
