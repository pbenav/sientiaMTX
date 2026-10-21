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
        $search = trim((string) $request->query('search', ''));

        $query = CircuitExecution::pendingForUser($user, $team->id)
            ->with(['circuitable', 'currentStep', 'initiator', 'template'])
            ->when($search !== '', function ($q) use ($search) {
                $cleanSearch = ltrim($search, '#');
                $q->where(function ($sub) use ($search, $cleanSearch) {
                    $sub->where('circuit_executions.title', 'like', "%{$search}%")
                        ->orWhere('circuit_executions.id', $cleanSearch)
                        ->orWhereHas('initiator', fn($i) => $i->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('template', fn($t) => $t->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('currentStep', fn($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        $executions = $query->paginate(15)->withQueryString();

        return view('circuits.executions.pending', compact('team', 'executions', 'search'));
    }

    /**
     * Listado general de tramitaciones del equipo con buscador multi-criterio.
     */
    public function index(Request $request, Team $team)
    {
        if (Auth::user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $query = $team->circuitExecutions()
            ->with(['circuitable', 'currentStep', 'initiator', 'template'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $cleanSearch = ltrim($search, '#');
                $q->where(function ($sub) use ($search, $cleanSearch) {
                    $sub->where('circuit_executions.title', 'like', "%{$search}%")
                        ->orWhere('circuit_executions.id', $cleanSearch)
                        ->orWhere('circuit_executions.notes', 'like', "%{$search}%")
                        ->orWhereHas('initiator', fn($i) => $i->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('template', fn($t) => $t->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('steps', fn($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        $executions = $query->paginate(15)->withQueryString();
        $templates = $team->circuitTemplates()->active()->get();
        $isOwnerOrAdmin = Auth::user()->is_admin || $team->owner_id === Auth::id() || Auth::user()->can('admin');

        return view('circuits.executions.index', compact('team', 'executions', 'templates', 'status', 'search', 'isOwnerOrAdmin'));
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
            return redirect()->back()->with('warning', __('circuits.already_in_circuit'));
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
            ->with('success', __('circuits.started_successfully'));
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
        $isOwnerOrAdmin = Auth::user()->is_admin || $team->owner_id === Auth::id() || Auth::user()->can('admin');

        return view('circuits.executions.show', compact('team', 'circuit', 'currentUserCanAct', 'isOwnerOrAdmin'));
    }

    /**
     * Muestra el portal / asistente interactivo de firma electrónica con AutoFirma para el paso actual.
     */
    public function signaturePortal(Request $request, Team $team, CircuitExecution $circuit)
    {
        if (Auth::user()->cannot('view', $team)) {
            abort(403);
        }

        if ($circuit->team_id !== $team->id) {
            abort(404);
        }

        if (!$circuit->is_active || !$circuit->currentStep) {
            return redirect()->route('teams.circuits.show', [$team, $circuit])
                ->with('warning', 'Esta tramitación no tiene ningún paso activo pendiente de resolución.');
        }

        $step = $circuit->currentStep;
        if (!$step->canUserAct(Auth::user(), $circuit)) {
            return redirect()->route('teams.circuits.show', [$team, $circuit])
                ->with('error', 'No tienes permisos asignados para firmar o resolver este paso.');
        }

        // Preparar el PDF de la tramitación sincronizado y listo para la firma del paso actual
        $pdfService = app(\App\Services\Circuits\CircuitPdfService::class);
        $pdfPath = $pdfService->generateAndAttach($circuit, $step, Auth::user());

        $allSteps = $circuit->steps()->orderBy('order', 'asc')->get();
        $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
        if ($stepIndex === false) {
            $stepIndex = max(0, $step->order - 1);
        }

        $existingSignaturesCount = $circuit->steps()->whereNotNull('signed_at')->count();

        return view('circuits.signature.portal', [
            'team' => $team,
            'circuit' => $circuit,
            'step' => $step,
            'stepIndex' => $stepIndex,
            'signerUser' => Auth::user(),
            'existingSignaturesCount' => $existingSignaturesCount,
        ]);
    }

    /**
     * Resuelve el paso actual de la tramitación (Aprobar, Devolver o Rechazar).
     */
    public function processStep(Request $request, Team $team, CircuitExecution $circuit, CircuitExecutionStep $step)
    {
        if (Auth::user()->cannot('view', $team)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __('teams.unauthorized_access')], 403);
            }
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($circuit->team_id !== $team->id || $step->circuit_execution_id !== $circuit->id) {
            abort(404);
        }

        $request->validate([
            'decision' => 'required|in:approved,returned,rejected',
            'comments' => 'nullable|string|max:2000',
            'return_to_order' => 'nullable|integer',
            'sign_now' => 'nullable',
            'signature_method' => 'nullable|string|in:internal_certificate,autofirma,manual_upload',
            'certificate_subject' => 'nullable|string|max:255',
            'signature_algorithm' => 'nullable|string|max:50',
            'signed_file' => 'nullable|file|mimes:pdf',
        ]);

        if (in_array($request->decision, ['returned', 'rejected']) && empty($request->comments)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Es obligatorio justificar el motivo al devolver o rechazar.'], 422);
            }
            return redirect()->back()->with('error', 'Es obligatorio justificar el motivo al devolver o rechazar.');
        }

        // Si la etapa exige firma digital, bloquear avance hasta que se complete con AutoFirma o subida de archivo
        if ($step->requires_signature && $request->decision === 'approved') {
            $sigMethod = $request->input('signature_method', 'autofirma');
            if ($sigMethod === 'autofirma' && !$request->hasFile('signed_file')) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => __('circuits.signature_required_error'),
                        'redirect' => route('teams.circuits.signature-portal', [$team, $circuit])
                    ], 422);
                }
                return redirect()->route('teams.circuits.signature-portal', [$team, $circuit])
                    ->with('warning', __('circuits.signature_required_error'));
            }

            if ($sigMethod === 'manual_upload' && !$request->hasFile('signed_file')) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => __('circuits.file_required_error')], 422);
                }
                return redirect()->back()->with('error', __('circuits.file_required_error'));
            }
        }

        try {
            $additionalData = $request->only([
                'return_to_order',
                'sign_now',
                'signature_method',
                'certificate_subject',
                'signature_algorithm'
            ]);

            if ($request->hasFile('signed_file')) {
                $file = $request->file('signed_file');
                $path = $file->store("circuits/{$circuit->id}/firmas", 'local');
                $fileHash = hash_file('sha256', $file->getRealPath());
                $sigMethod = $request->input('signature_method', 'autofirma');
                $additionalData['signature_method'] = $sigMethod;
                $additionalData['signature_data'] = [
                    'signed_file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'sha256_hash' => $fileHash,
                    'method' => $sigMethod,
                    'signer_name' => Auth::user()->name,
                    'signer_email' => Auth::user()->email,
                ];

                $circuit->updateQuietly(['signed_document_path' => $path]);
            }

            if ($request->filled('signature_data_json')) {
                $decoded = json_decode($request->input('signature_data_json'), true);
                if (is_array($decoded)) {
                    $additionalData['signature_data'] = array_merge($additionalData['signature_data'] ?? [], $decoded);
                }
            }

            $this->executionService->processStep(
                $step,
                Auth::user(),
                $request->decision,
                $request->comments,
                $additionalData
            );

            $msg = match ($request->decision) {
                'approved' => 'Paso aprobado y conforme.' . ($step->requires_signature || !empty($additionalData['sign_now']) ? ' Firma digital registrada en la diligencia oficial.' : ''),
                'returned' => 'Trámite devuelto para subsanación con las observaciones indicadas.',
                'rejected' => 'Trámite rechazado.',
            };

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => route('teams.circuits.show', [$team, $circuit]),
                ]);
            }

            return redirect()->route('teams.circuits.show', [$team, $circuit])->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Sirve o descarga el documento PDF oficial consolidado con todas las firmas del circuito.
     */
    public function downloadDocument(Request $request, Team $team, CircuitExecution $circuit)
    {
        if (Auth::user()->cannot('view', $team)) {
            abort(403);
        }

        if ($circuit->team_id !== $team->id) {
            abort(404);
        }

        $pdfService = app(\App\Services\Circuits\CircuitPdfService::class);
        $filePath = null;

        // Si se solicita el PDF preparado específicamente para la firma de un paso en curso
        if ($forStepId = $request->query('for_step')) {
            $signingStep = $circuit->steps()->find($forStepId);
            if ($signingStep && $signingStep->canUserAct(Auth::user(), $circuit)) {
                $filePath = $pdfService->generateAndAttach($circuit, $signingStep, Auth::user());
            }
        }

        if (!$filePath) {
            $filePath = $circuit->signed_document_path;
        }

        if (!$filePath || !\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            $filePath = $pdfService->generateAndAttach($circuit);
        }

        $fileName = basename($filePath);
        $disposition = $request->query('download') ? 'attachment' : 'inline';

        return \Illuminate\Support\Facades\Storage::disk('local')->response(
            $filePath,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$fileName}\"",
            ]
        );
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

    /**
     * Elimina definitivamente / purga una tramitación de prueba (solo Administradores o Propietario del equipo).
     */
    public function destroy(Request $request, Team $team, CircuitExecution $circuit)
    {
        $user = Auth::user();
        $isOwnerOrAdmin = $user->is_admin || $team->owner_id === $user->id || $user->can('admin');

        if (!$isOwnerOrAdmin) {
            abort(403, 'Solo un administrador o el propietario del equipo puede eliminar tramitaciones.');
        }

        if ($circuit->team_id !== $team->id) {
            abort(404);
        }

        try {
            $this->executionService->deleteCircuit($circuit, $user);
            return redirect()->route('teams.circuits.index', $team)
                ->with('success', 'La tramitación ha sido eliminada del sistema.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

