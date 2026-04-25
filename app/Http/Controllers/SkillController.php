<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkillController extends Controller
{
    public function index(\App\Models\Team $team = null)
    {
        $query = Skill::with(['team', 'tasks'])->withCount('tasks')->orderBy('name');

        if ($team) {
            $this->authorize('update', $team);
            return redirect()->route('teams.edit', ['team' => $team, 'tab' => 'skills']);
        }

        if (!auth()->user()->can('admin')) {
            abort(403);
        }
        $query->whereNull('team_id');
        $teams = \App\Models\Team::orderBy('name')->get();

        $skills = $query->get();
        return view('settings.skills', compact('skills', 'teams', 'team'));
    }

    public function store(Request $request, \App\Models\Team $team = null)
    {
        if ($team) {
            $this->authorize('update', $team);
        } else {
            if (!auth()->user()->can('admin')) {
                abort(403);
            }
        }

        $request->validate([
            'team_id' => 'nullable|exists:teams,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $data = $request->all();
        // Force team_id if we are in team scope
        if ($team) {
            $data['team_id'] = $team->id;
        }

        $skill = Skill::create($data);

        // If a local skill shadows a global one, migrate existing tasks for this team
        if ($team) {
            $globalSkill = Skill::whereNull('team_id')->where('name', $skill->name)->first();
            if ($globalSkill) {
                $teamTaskIds = \App\Models\Task::where('team_id', $team->id)->pluck('id');
                if ($teamTaskIds->isNotEmpty()) {
                    \Illuminate\Support\Facades\DB::table('skill_task')
                        ->whereIn('task_id', $teamTaskIds)
                        ->where('skill_id', $globalSkill->id)
                        ->update(['skill_id' => $skill->id]);
                }
            }
        }

        return back()->with('success', 'Habilidad creada correctamente.');
    }

    public function update(Request $request, \App\Models\Team $team = null, Skill $skill)
    {
        // Handle case where route might be global but skill belongs to team
        if ($team) {
            $this->authorize('update', $team);
            if ($skill->team_id !== $team->id) abort(403);
        } else {
            if (!auth()->user()->can('admin')) {
                abort(403);
            }
        }

        $request->validate([
            'team_id' => 'nullable|exists:teams,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $data = $request->all();
        if ($team) {
            $data['team_id'] = $team->id;
        }

        $skill->update($data);

        return back()->with('success', 'Habilidad actualizada correctamente.');
    }

    public function destroy(\App\Models\Team $team = null, Skill $skill)
    {
        if ($team) {
            $this->authorize('update', $team);
            if ($skill->team_id !== $team->id) abort(403);
        } else {
            if (!auth()->user()->can('admin')) {
                abort(403);
            }
        }

        // Check if there are tasks using this skill
        if ($skill->tasks()->exists()) {
            return back()->with('error', 'No se puede eliminar una habilidad que tiene tareas asociadas.');
        }

        $skill->delete();

        return back()->with('success', 'Habilidad eliminada correctamente.');
    }

    public function inherit(\App\Models\Team $team)
    {
        $this->authorize('update', $team);

        $globalSkills = Skill::whereNull('team_id')->get();
        $inheritedCount = 0;

        foreach ($globalSkills as $globalSkill) {
            // Check if team already has this skill by name
            $exists = Skill::where('team_id', $team->id)
                ->where('name', $globalSkill->name)
                ->exists();

            if (!$exists) {
                $newSkill = Skill::create([
                    'team_id' => $team->id,
                    'name' => $globalSkill->name,
                    'description' => $globalSkill->description,
                    'color' => $globalSkill->color,
                    'icon' => $globalSkill->icon,
                ]);

                // Migrate existing tasks from global skill to new local skill safely
                $teamTaskIds = \App\Models\Task::where('team_id', $team->id)->pluck('id');
                if ($teamTaskIds->isNotEmpty()) {
                    \Illuminate\Support\Facades\DB::table('skill_task')
                        ->whereIn('task_id', $teamTaskIds)
                        ->where('skill_id', $globalSkill->id)
                        ->update(['skill_id' => $newSkill->id]);
                }

                $inheritedCount++;
            }
        }

        if ($inheritedCount > 0) {
            return back()->with('success', "Se han heredado $inheritedCount habilidades correctamente.");
        }

        return back()->with('info', "Todas las habilidades globales ya estaban presentes en el equipo.");
    }
}
