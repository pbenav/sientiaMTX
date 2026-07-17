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

        // Fetch all activities of type document that are completed or archived, or just all documents.
        // Let's bring all documents for the wiki, prioritizing completed ones.
        $documents = Activity::where('team_id', $team->id)
            ->where('type', 'document')
            ->orderBy('is_archived', 'asc') // Active first
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
