<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Controlador para la biblioteca de documentos del equipo.
 *
 * Permite navegar y filtrar documentos del equipo por título, descripción,
 * metadatos, notas, estado y rango de fechas. Muestra el primer documento
 * seleccionado o uno específico por ID.
 */
class TeamLibraryController extends Controller
{
    /**
     * Muestra la lista de documentos del equipo con filtros aplicados.
     *
     * Filtra por texto de búsqueda (título, descripción, metadata, notas),
     * estado, y rango de fechas. Ordena documentos activos primero y
     * selecciona el documento activo por parámetro 'doc' o el primero de la lista.
     *
     * @param  \App\Models\Team  $team  Equipo cuya biblioteca se consulta
     * @param  \Illuminate\Http\Request  $request  Filtros: q (búsqueda), status, date_from, date_to, doc
     * @return \Illuminate\View\View Vista 'teams.library.index' con documentos y filtros
     */
    public function index(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $isManager = $team->isManager(auth()->user());

        $query = Activity::where('team_id', $team->id)
            ->where('type', 'document')
            ->visibleTo(auth()->user(), $isManager);

        if ($request->filled('q')) {
            $searchTerm = '%' . $request->input('q') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm)
                  ->orWhere('metadata', 'LIKE', $searchTerm)
                  ->orWhereHas('notes', function ($nq) use ($searchTerm) {
                      $nq->where('content', 'LIKE', $searchTerm);
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status->value', $request->input('status'));
        }

        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : '1970-01-01';
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : Carbon::today()->toDateString();

        $query->whereDate('created_at', '>=', $dateFrom);
        $query->whereDate('created_at', '<=', $dateTo);

        $documents = $query->orderBy('is_archived', 'asc') // Active first
            ->orderBy('created_at', 'desc')
            ->get();

        // If a specific document is requested
        $activeDocumentId = $request->get('doc');
        $activeDocument = null;
        
        if ($activeDocumentId) {
            $activeDocument = $documents->firstWhere('id', $activeDocumentId);
        }
        
        // If no document selected but there are documents, pick the first one
        if (!$activeDocument && $documents->isNotEmpty()) {
            $activeDocument = $documents->first();
        }

        return view('teams.library.index', compact('team', 'documents', 'activeDocument', 'dateFrom', 'dateTo'));
    }
}
