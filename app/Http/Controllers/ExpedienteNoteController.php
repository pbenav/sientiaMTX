<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Team;
use App\Models\Expediente;
use App\Models\ExpedienteNote;

/**
 * Controlador para la gestión de notas dentro de un expediente de un equipo.
 *
 * Permite crear, editar y eliminar notas de expediente con soporte para notas privadas
 * (visibles solo por su autor o coordinadores del equipo).
 *
 * Rutas asociadas:
 *   - POST /teams/{team}/expedientes/{expediente}/notes
 *   - PUT/PATCH /teams/{team}/expedientes/{expediente}/notes/{note}
 *   - DELETE /teams/{team}/expedientes/{expediente}/notes/{note}
 */
class ExpedienteNoteController extends Controller
{
    /**
     * Crea una nueva nota dentro de un expediente.
     *
     * @param Request $request
     * @param Team $team
     * @param Expediente $expediente
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Actualiza el contenido y visibilidad de una nota de expediente.
     *
     * Prohíbe la edición de notas privadas a usuarios que no sean su autor.
     *
     * @param Request $request
     * @param Team $team
     * @param Expediente $expediente
     * @param ExpedienteNote $note
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Elimina una nota de expediente.
     *
     * Solo el autor de la nota o un coordinador del equipo pueden eliminarla.
     *
     * @param Team $team
     * @param Expediente $expediente
     * @param ExpedienteNote $note
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team, Expediente $expediente, ExpedienteNote $note)
    {
        if ($note->user_id !== auth()->id() && !auth()->user()->isCoordinator($team)) {
            abort(403);
        }

        $note->delete();

        return back()->with('success', 'Nota eliminada correctamente.');
    }
}
