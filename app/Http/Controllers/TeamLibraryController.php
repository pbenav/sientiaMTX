<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Activity;
use Illuminate\Http\Request;

class TeamLibraryController extends Controller
{
    public function index(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $isManager = $team->isManager(auth()->user());

        $query = Activity::where('team_id', $team->id)
            ->where('type', 'document')
            ->visibleTo(auth()->user(), $isManager);

        if ($request->filled('q')) {
            $searchTerm = '%' . $request->input('q') . '%';
            $query->where('title', 'LIKE', $searchTerm);
        }

        if ($request->filled('status')) {
            $query->where('status->value', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

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

        return view('teams.library.index', compact('team', 'documents', 'activeDocument'));
    }
}
