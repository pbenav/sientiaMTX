<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers\Circuits;

use App\Http\Controllers\Controller;
use App\Models\CircuitExecution;
use App\Models\CircuitPublication;
use App\Models\Team;
use App\Services\Circuits\CircuitPublicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CircuitPublicBoardController extends Controller
{
    public function __construct(
        protected CircuitPublicationService $publicationService
    ) {}

    /**
     * Vista de gestión interna del tablón edictal para miembros del equipo.
     */
    public function index(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $status = $request->query('status');
        $search = $request->query('search');

        $publications = $team->circuitPublications()
            ->with(['execution.circuitable', 'publisher'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('document_code', 'like', "%{$search}%");
            }))
            ->latest('publication_date')
            ->paginate(15);

        return view('circuits.board.index', compact('team', 'publications', 'status', 'search'));
    }

    /**
     * Publica directamente una tramitación aprobada en el tablón.
     */
    public function publishDirectly(Request $request, Team $team, CircuitExecution $circuit)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($circuit->team_id !== $team->id) {
            abort(404);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'summary' => 'nullable|string|max:1000',
            'expiration_date' => 'nullable|date|after_or_equal:today',
        ]);

        $publication = $this->publicationService->publish(
            $circuit,
            Auth::user(),
            $request->only(['title', 'summary', 'expiration_date'])
        );

        return redirect()->route('teams.circuits.board.index', $team)
            ->with('success', 'Documento publicado con éxito en el tablón público con código ' . $publication->document_code);
    }

    /**
     * Retira una publicación del tablón público.
     */
    public function withdraw(Request $request, Team $team, CircuitPublication $publication)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($publication->team_id !== $team->id) {
            abort(404);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->publicationService->withdraw($publication, Auth::user(), $request->reason);

        return redirect()->route('teams.circuits.board.index', $team)
            ->with('info', 'La publicación ha sido retirada del tablón público.');
    }

    /**
     * VISTA PÚBLICA (Ciudadanía): Tablón edictal del equipo / organismo.
     */
    public function teamBoard(Request $request, Team $team)
    {
        $publications = $team->circuitPublications()
            ->active()
            ->latest('publication_date')
            ->paginate(20);

        return view('circuits.public.board', compact('team', 'publications'));
    }

    /**
     * VISTA PÚBLICA (Ciudadanía): Detalle de un documento o edicto publicado.
     */
    public function show(Request $request, string $slug)
    {
        $publication = CircuitPublication::where('public_slug', $slug)
            ->with(['team', 'execution.circuitable', 'execution.steps.actionUser'])
            ->firstOrFail();

        return view('circuits.public.show', compact('publication'));
    }
}

