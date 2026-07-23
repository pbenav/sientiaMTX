<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controlador para la gestión de habilidades (skills) globales y por equipo.
 *
 * Permite crear, actualizar, eliminar habilidades, heredar habilidades globales
 * a equipos específicos, y consultar tareas asociadas a una habilidad.
 */
class SkillController extends Controller
{
    /**
     * Lista las habilidades globales (admin) o muestra la vista de edición de habilidades del equipo.
     *
     * Si se proporciona $team, verifica permisos de actualización y redirige a la vista de edición.
     * Si no, verifica permisos de admin y retorna la vista de administración global.
     *
     * @param  \App\Models\Team|null  $team  Equipo a editar (opcional)
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View Redirección a edición de equipo o vista de administración
     */
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

    /**
     * Crea una nueva habilidad (global o de equipo).
     *
     * Si se crea dentro de un equipo y existe una habilidad global con el mismo nombre,
     * migra las actividades del equipo que usaban la habilidad global a la nueva local.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener name (obligatorio), team_id, description, color, icon (opcionales)
     * @param  \App\Models\Team|null  $team  Equipo al que pertenece la habilidad (opcional, para global)
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito
     */
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
                $teamActivityIds = \App\Models\Activity::where('team_id', $team->id)->pluck('id');
                if ($teamActivityIds->isNotEmpty()) {
                    \Illuminate\Support\Facades\DB::table('activity_skills')
                        ->whereIn('activity_id', $teamActivityIds)
                        ->where('skill_id', $globalSkill->id)
                        ->update(['skill_id' => $skill->id]);
                }
            }
        }

        return back()->with('success', 'Habilidad creada correctamente.');
    }

    /**
     * Actualiza una habilidad existente.
     *
     * Verifica permisos de admin (global) o actualización de equipo (local).
     * Si se proporciona un team_id en el request, lo asigna a la habilidad.
     *
     * @param  \Illuminate\Http\Request  $request  Campos a actualizar (name, description, color, icon, team_id)
     * @param  \App\Models\Team|null  $team  Equipo contexto (opcional)
     * @param  \App\Models\Skill  $skill  Habilidad a actualizar
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito
     */
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

    /**
     * Elimina una habilidad si no tiene tareas asociadas.
     *
     * Verifica permisos de admin (global) o actualización de equipo (local).
     * Bloquea la eliminación si hay actividades que usan la habilidad.
     *
     * @param  \App\Models\Team|null  $team  Equipo contexto (opcional)
     * @param  \App\Models\Skill  $skill  Habilidad a eliminar
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito o error
     */
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

    /**
     * Hereda todas las habilidades globales no existentes en el equipo.
     *
     * Crea copias locales de las habilidades globales y migra las actividades
     * existentes del equipo que usaban la habilidad global a la nueva copia local.
     *
     * @param  \App\Models\Team  $team  Equipo que heredará las habilidades
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito o info
     */
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

                // Migrate existing activities from global skill to new local skill safely
                $teamActivityIds = \App\Models\Activity::where('team_id', $team->id)->pluck('id');
                if ($teamActivityIds->isNotEmpty()) {
                    \Illuminate\Support\Facades\DB::table('activity_skills')
                        ->whereIn('activity_id', $teamActivityIds)
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

    /**
     * Obtiene las tareas asociadas a una habilidad específica (API JSON).
     *
     * Consulta actividades de tipo 'task' que tengan la habilidad indicada,
     * incluyendo el cognitive_load extraído de metadata.
     *
     * @param  \Illuminate\Http\Request  $request  Debe estar autenticado
     * @param  \App\Models\Team  $team  Equipo de las tareas
     * @param  string  $skillName  Nombre de la habilidad para filtrar
     * @return \Illuminate\Http\JsonResponse Respuesta con tareas paginadas
     */
    public function tasks(Request $request, \App\Models\Team $team, $skillName)
    {
        $tasks = \App\Models\Activity::visibleTo(auth()->user(), $team->isManager(auth()->user()))
            ->where('activities.type', 'task')
            ->join('activity_skills', 'activities.id', '=', 'activity_skills.activity_id')
            ->join('skills', 'activity_skills.skill_id', '=', 'skills.id')
            ->where('activities.team_id', $team->id)
            ->where('skills.name', $skillName)
            ->select(
                'activities.id', 
                'activities.title', 
                'activities.status', 
                \Illuminate\Support\Facades\DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(activities.metadata, "$.cognitive_load")) AS UNSIGNED) as cognitive_load'),
                'activities.created_at'
            )
            ->distinct()
            ->orderBy('activities.created_at', 'desc')
            ->paginate(10);

        return response()->json($tasks);
    }
}
