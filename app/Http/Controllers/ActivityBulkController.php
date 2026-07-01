<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Services\ActivityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityBulkController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function bulkUpdate(Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:activities,id',
            'field' => 'required|string|in:status,priority,assigned_user_id',
            'value' => 'required'
        ]);

        $user = auth()->user();
        $validActivityIds = [];
        
        foreach ($request->task_ids as $activityId) {
            $activity = Activity::find($activityId);
            if ($activity && $user->can('update', $activity)) {
                $validActivityIds[] = $activityId;
            }
        }

        if (empty($validActivityIds)) {
            return back()->with('warning', 'No tienes permisos para actualizar las actividades seleccionadas.');
        }

        // We do bulk update manually here since ActivityService might not have it yet
        $count = 0;
        foreach ($validActivityIds as $id) {
            $activity = Activity::find($id);
            if ($request->field === 'status') {
                $this->activityService->changeStatus($activity, $request->value);
                $count++;
            } elseif ($request->field === 'priority') {
                $activity->update(['priority' => $request->value]);
                $count++;
            } elseif ($request->field === 'assigned_user_id') {
                $activity->assignments()->whereNotNull('user_id')->delete(); // Clear old user assignments
                if ($request->value) {
                    $activity->assignments()->create([
                        'user_id' => $request->value,
                        'assigned_by_id' => $user->id,
                        'assigned_at' => now(),
                    ]);
                }
                $count++;
            }
        }

        return back()->with('success', "Se han actualizado {$count} actividades correctamente.");
    }

    public function bulkDelete(Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:activities,id'
        ]);

        $activities = Activity::whereIn('id', $request->task_ids)
            ->where('team_id', $team->id)
            ->get();
            
        $deletedCount = 0;

        foreach ($activities as $activity) {
            if ($request->user()->can('delete', $activity)) {
                $this->activityService->delete($activity);
                $deletedCount++;
            }
        }

        return redirect()->route('teams.activities.index', $team)
            ->with('success', "$deletedCount actividades eliminadas correctamente.");
    }

    public function bulkMerge(Request $request, Team $team)
    {
        // Simple merge fallback. Just tell them not supported or do a basic one.
        // For now, redirect with info
        return back()->with('info', 'La fusión masiva de actividades avanzadas requiere revisión manual. Por favor, usa la fusión individual.');
    }
}
