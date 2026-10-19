<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers\Circuits;

use App\Http\Controllers\Controller;
use App\Models\CircuitTemplate;
use App\Models\CircuitTemplateStep;
use App\Models\Team;
use App\Models\TeamRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CircuitTemplateController extends Controller
{
    /**
     * Listado de plantillas de circuito del equipo.
     */
    public function index(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $templates = $team->circuitTemplates()
            ->with(['creator', 'steps.assignedUser', 'steps.assignedRole'])
            ->withCount('executions')
            ->latest()
            ->paginate(15);

        return view('circuits.templates.index', compact('team', 'templates'));
    }

    /**
     * Formulario de creación de plantilla de circuito.
     */
    public function create(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $members = $team->members()->orderBy('name')->get();
        $roles = TeamRole::orderBy('name')->get();

        return view('circuits.templates.create', compact('team', 'members', 'roles'));
    }

    /**
     * Almacena una nueva plantilla con sus etapas.
     */
    public function store(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_type' => 'required|in:activity,expediente,both',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.action_type' => 'required|in:endorsement,justification,approval,publication',
            'steps.*.assignee_type' => 'required|in:specific_user,team_role,activity_responsible,creator,dynamic',
            'steps.*.assignee_id' => 'nullable|integer',
            'steps.*.requires_signature' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $team) {
            $template = CircuitTemplate::create([
                'team_id' => $team->id,
                'created_by_id' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description,
                'target_type' => $request->target_type,
                'is_active' => $request->boolean('is_active', true),
                'settings' => [
                    'auto_publish' => $request->boolean('auto_publish', false),
                ],
            ]);

            foreach ($request->steps as $index => $stepData) {
                CircuitTemplateStep::create([
                    'circuit_template_id' => $template->id,
                    'order' => $index + 1,
                    'name' => $stepData['name'],
                    'description' => $stepData['description'] ?? null,
                    'action_type' => $stepData['action_type'],
                    'assignee_type' => $stepData['assignee_type'],
                    'assignee_id' => $stepData['assignee_id'] ?? null,
                    'is_mandatory' => true,
                    'allow_return' => true,
                    'requires_signature' => !empty($stepData['requires_signature']),
                    'action_config' => [
                        'auto_publish' => $stepData['action_type'] === CircuitTemplateStep::ACTION_PUBLICATION,
                    ],
                ]);
            }
        });

        return redirect()->route('teams.circuits.templates.index', $team)
            ->with('success', 'Plantilla de circuito creada correctamente.');
    }

    /**
     * Formulario de edición de plantilla.
     */
    public function edit(Request $request, Team $team, CircuitTemplate $template)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($template->team_id !== $team->id) {
            abort(404);
        }

        $template->load('steps');
        $members = $team->members()->orderBy('name')->get();
        $roles = TeamRole::orderBy('name')->get();

        return view('circuits.templates.edit', compact('team', 'template', 'members', 'roles'));
    }

    /**
     * Actualiza una plantilla existente y sus etapas.
     */
    public function update(Request $request, Team $team, CircuitTemplate $template)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($template->team_id !== $team->id) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_type' => 'required|in:activity,expediente,both',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.action_type' => 'required|in:endorsement,justification,approval,publication',
            'steps.*.assignee_type' => 'required|in:specific_user,team_role,activity_responsible,creator,dynamic',
            'steps.*.assignee_id' => 'nullable|integer',
            'steps.*.requires_signature' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $template) {
            $template->update([
                'name' => $request->name,
                'description' => $request->description,
                'target_type' => $request->target_type,
                'is_active' => $request->boolean('is_active', true),
                'settings' => [
                    'auto_publish' => $request->boolean('auto_publish', false),
                ],
            ]);

            // Reemplazar etapas
            $template->steps()->delete();

            foreach ($request->steps as $index => $stepData) {
                CircuitTemplateStep::create([
                    'circuit_template_id' => $template->id,
                    'order' => $index + 1,
                    'name' => $stepData['name'],
                    'description' => $stepData['description'] ?? null,
                    'action_type' => $stepData['action_type'],
                    'assignee_type' => $stepData['assignee_type'],
                    'assignee_id' => $stepData['assignee_id'] ?? null,
                    'is_mandatory' => true,
                    'allow_return' => true,
                    'requires_signature' => !empty($stepData['requires_signature']),
                    'action_config' => [
                        'auto_publish' => $stepData['action_type'] === CircuitTemplateStep::ACTION_PUBLICATION,
                    ],
                ]);
            }
        });

        return redirect()->route('teams.circuits.templates.index', $team)
            ->with('success', 'Plantilla de circuito actualizada correctamente.');
    }

    /**
     * Elimina una plantilla de circuito.
     */
    public function destroy(Request $request, Team $team, CircuitTemplate $template)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($template->team_id !== $team->id) {
            abort(404);
        }

        $template->delete();

        return redirect()->route('teams.circuits.templates.index', $team)
            ->with('success', 'Plantilla eliminada correctamente.');
    }
}

