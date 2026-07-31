<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BadgeController extends Controller
{
    /**
     * List all badges (Global Admin Settings).
     */
    public function indexAdmin()
    {
        if (!auth()->user()->can('admin')) {
            abort(403);
        }

        $badges = Badge::orderBy('name')->get();

        return view('settings.badges', compact('badges'));
    }

    /**
     * Store a new badge.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:badges,name',
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'criteria_type' => 'required|string|max:50',
            'criteria_threshold' => 'required|integer|min:1',
        ]);

        Badge::create($validated);

        return back()->with('success', 'Medalla creada correctamente.');
    }

    /**
     * Update an existing badge.
     */
    public function update(Request $request, Badge $badge)
    {
        if (!auth()->user()->can('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('badges', 'name')->ignore($badge->id)],
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'criteria_type' => 'required|string|max:50',
            'criteria_threshold' => 'required|integer|min:1',
        ]);

        $badge->update($validated);

        return back()->with('success', 'Medalla actualizada correctamente.');
    }

    /**
     * Delete a badge.
     */
    public function destroy(Badge $badge)
    {
        if (!auth()->user()->can('admin')) {
            abort(403);
        }

        $badge->delete();

        return back()->with('success', 'Medalla eliminada correctamente.');
    }

    /**
     * View for team coordinators to manage which badges are active for their team.
     */
    public function teamSettings(Team $team)
    {
        $this->authorize('update', $team);

        $badges = Badge::orderBy('name')->get();
        $enabledBadges = $team->settings['enabled_badges'] ?? null;
        
        // Si nunca se ha configurado (null), lo tratamos como si todas estuvieran habilitadas
        if ($enabledBadges === null) {
            $enabledBadges = $badges->pluck('id')->toArray();
        }

        return view('teams.settings.badges', compact('team', 'badges', 'enabledBadges'));
    }

    /**
     * Update which badges are active for the team.
     */
    public function updateTeamSettings(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'enabled_badges' => 'nullable|array',
            'enabled_badges.*' => 'exists:badges,id',
        ]);

        $settings = $team->settings ?? [];
        // Guardamos las IDs como enteros
        $settings['enabled_badges'] = array_map('intval', $validated['enabled_badges'] ?? []);

        $team->update(['settings' => $settings]);

        return back()->with('success', 'Configuración de medallas del equipo actualizada correctamente.');
    }
}
