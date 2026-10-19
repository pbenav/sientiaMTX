<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers\Circuits;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CircuitExecution;
use App\Models\CircuitExecutionStep;
use App\Models\CircuitTemplate;
use App\Models\Expediente;
use App\Models\Team;
use App\Services\Circuits\CircuitExecutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CircuitExecutionController extends Controller
{
    public function __construct(
        protected CircuitExecutionService $executionService
    ) {}

    /**
     * Bandeja de trámites pendientes de visto bueno / firma del usuario autenticado.
     */
    public function pending(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $user = Auth::user();

        $executions = CircuitExecution::pendingForUser($user, $team->id)
            ->with(['circuitable', 'currentStep', 'initiator', 'template'])
            ->latest()
            ->paginate(15);

        return view('circuits.executions.pending', compact('team', 'executions'));
    }

    /**
     * Listado general de tramitaciones del equipo.
     */
    public function index(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $status = $request->query('status');
        $search = $request->query('search');

        $query = $team->circuitExecutions()
            ->with(['circuitable', 'currentStep', 'initiator', 'template'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->latest();

        $executions = $query->paginate(15)->withQueryString();
        $templates = $team->circuitTemplates()->active()->get();

        return view('circuits.executions.index', compact('team', 'executions', 'templates', 'status', 'search'));
    }

    /**
     * Inicia una nueva tramitación para una actividad o expediente.
     */
    public function start(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $request->validate([
            'circuitable_type' => 'required|string',
            'circuitable_id' => 'required|integer',
            'circuit_template_id' => 'nullable|exists:circuit_templates,id',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $type = $request->input('circuitable_type');
        $id = $request->input('circuitable_id');

        $circuitable = match ($type) {
            'activity', Activity::class => Activity::where('team_id', $team->id)->findOrFail($id),
            'expediente', Expediente::class => Expediente::where('team_id', $team->id)->findOrFail($id),
            default => abort(400, 'Tipo de elemento no válido para circuito'),
        };

        if ($circuitable->isInCircuit) {
            return redirect()->back()->with('warning', __('circuits.already_in_circuit', [], 'es') ?? 'Este elemento ya tiene una tramitación activa en curso.');
        }

        $template = $request->filled('circuit_template_id')
            ? CircuitTemplate::where('team_id', $team->id)->findOrFail($request->circuit_template_id)
            : null;

        $execution = $this->executionService->startCircuit(
            $circuitable,
            $template,
            Auth::user(),
            $request->only(['title', 'notes'])
        );

        return redirect()->route('teams.circuits.show', [$team, $execution])
            ->with('success', __('circuits.started_successfully', [], 'es') ?? 'Tramitación iniciada correctamente.');
    }

    /**
     * Muestra el detalle de una tramitación con su stepper y acciones disponibles.
     */
    public function show(Request $request, Team $team, CircuitExecution $circuit)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($circuit->team_id !== $team->id) {
            abort(404);
        }

        $circuit->load([
            'steps.assignedUser',
            'steps.assignedRole',
            'steps.actionUser',
            'currentStep',
            'circuitable',
            'initiator',
            'template',
            'publication'
        ]);

        $currentUserCanAct = $circuit->canUserAct(Auth::user());

        return view('circuits.executions.show', compact('team', 'circuit', 'currentUserCanAct'));
    }

    /**
     * Resuelve el paso actual de la tramitación (Aprobar, Devolver o Rechazar).
     */
    public function processStep(Request $request, Team $team, CircuitExecution $circuit, CircuitExecutionStep $step)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($circuit->team_id !== $team->id || $step->circuit_execution_id !== $circuit->id) {
            abort(404);
        }

        $request->validate([
            'decision' => 'required|in:approved,returned,rejected',
            'comments' => 'nullable|string|max:2000',
            'return_to_order' => 'nullable|integer',
        ]);

        if (in_array($request->decision, ['returned', 'rejected']) && empty($request->comments)) {
            return redirect()->back()->with('error', 'Es obligatorio justificar el motivo al devolver o rechazar.');
        }

        try {
            $this->executionService->processStep(
                $step,
                Auth::user(),
                $request->decision,
                $request->comments,
                $request->only(['return_to_order'])
            );

            $msg = match ($request->decision) {
                'approved' => 'Paso aprobado y conforme.',
                'returned' => 'Trámite devuelto para subsanación con las observaciones indicadas.',
                'rejected' => 'Trámite rechazado.',
            };

            return redirect()->route('teams.circuits.show', [$team, $circuit])->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancela una tramitación en curso.
     */
    public function cancel(Request $request, Team $team, CircuitExecution $circuit)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($circuit->team_id !== $team->id) {
            abort(404);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->executionService->cancelCircuit($circuit, Auth::user(), $request->reason);
            return redirect()->route('teams.circuits.show', [$team, $circuit])
                ->with('warning', 'La tramitación ha sido cancelada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

