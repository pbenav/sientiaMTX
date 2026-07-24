<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Activity;
use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityService;

class TeamTrashController extends Controller
{
    /**
     * Display a listing of the trashed resources.
     */
    public function index(Team $team)
    {
        $this->authorize('view', $team);

        // Fetch trashed activities
        $activities = Activity::onlyTrashed()
            ->where('team_id', $team->id)
            ->with(['creator'])
            ->orderBy('deleted_at', 'desc')
            ->get();

        // Fetch trashed expedientes
        $expedientes = Expediente::onlyTrashed()
            ->where('team_id', $team->id)
            ->with('creator')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('teams.trash.index', compact('team', 'activities', 'expedientes'));
    }

    /**
     * Restore a trashed activity.
     */
    public function restoreActivity(Request $request, Team $team, $id)
    {
        $this->authorize('view', $team);
        
        $activity = Activity::onlyTrashed()->where('team_id', $team->id)->findOrFail($id);
        
        if ($request->user()->cannot('delete', $activity) && $request->user()->cannot('update', $activity)) {
            abort(403, 'No tienes permiso para restaurar esta actividad.');
        }

        $activity->restore();

        return back()->with('success', 'Actividad restaurada correctamente.');
    }

    /**
     * Force delete a trashed activity.
     */
    public function forceDeleteActivity(Request $request, Team $team, $id, ActivityService $activityService)
    {
        $this->authorize('update', $team); // Requires team admin or similar

        $activity = Activity::onlyTrashed()->where('team_id', $team->id)->findOrFail($id);
        
        // Use service to cleanly remove if it supports forceDelete, else we use forceDelete directly
        $activity->forceDelete();

        return back()->with('success', 'Actividad eliminada permanentemente.');
    }

    /**
     * Restore a trashed expediente.
     */
    public function restoreExpediente(Request $request, Team $team, $id)
    {
        $this->authorize('view', $team);
        
        $expediente = Expediente::onlyTrashed()->where('team_id', $team->id)->findOrFail($id);
        
        if ($request->user()->cannot('update', $expediente)) {
            abort(403, 'No tienes permiso para restaurar este expediente.');
        }

        $expediente->restore();

        return back()->with('success', 'Expediente restaurado correctamente.');
    }

    /**
     * Force delete a trashed expediente.
     */
    public function forceDeleteExpediente(Request $request, Team $team, $id)
    {
        $this->authorize('update', $team); // Requires team admin

        $expediente = Expediente::onlyTrashed()->where('team_id', $team->id)->findOrFail($id);
        $expediente->forceDelete();

        return back()->with('success', 'Expediente eliminado permanentemente.');
    }

    /**
     * Empty the entire trash bin for the team.
     */
    public function empty(Request $request, Team $team)
    {
        $this->authorize('update', $team); // Only team managers/admins

        DB::transaction(function () use ($team) {
            // Force delete all trashed activities
            Activity::onlyTrashed()->where('team_id', $team->id)->forceDelete();
            
            // Force delete all trashed expedientes
            Expediente::onlyTrashed()->where('team_id', $team->id)->forceDelete();
        });

        return redirect()->route('teams.trash.index', $team)->with('success', 'La papelera ha sido vaciada de forma permanente.');
    }
}
