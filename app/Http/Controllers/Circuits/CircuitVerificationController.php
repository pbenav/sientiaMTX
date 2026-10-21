<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers\Circuits;

use App\Http\Controllers\Controller;
use App\Models\CircuitExecution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CircuitVerificationController extends Controller
{
    /**
     * Muestra la página pública de verificación y cotejo de documentos (CSV).
     * Permite a cualquier ciudadano o auditor comprobar la validez de un documento
     * tramitado según la Ley 39/2015 y el Reglamento eIDAS (UE 910/2014).
     */
    public function show(Request $request, ?string $csv = null)
    {
        $inputCsv = trim($csv ?? $request->query('csv', ''));
        $highlightHash = $request->query('hash');
        $highlightStep = $request->query('step');

        if (empty($inputCsv)) {
            return view('circuits.public.verify', [
                'csv' => null,
                'circuit' => null,
                'searched' => false,
                'highlightHash' => $highlightHash,
                'highlightStep' => $highlightStep,
            ]);
        }

        // 1. Buscar coincidencia exacta por CSV en metadata
        $circuit = CircuitExecution::where('metadata->csv', $inputCsv)
            ->with(['team', 'steps.actionUser', 'steps.assignedUser', 'initiator'])
            ->first();

        // 2. Si no coincide exactamente, buscar insensible a mayúsculas/minúsculas
        if (!$circuit) {
            $circuit = CircuitExecution::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.csv'))) = ?", [strtolower($inputCsv)])
                ->with(['team', 'steps.actionUser', 'steps.assignedUser', 'initiator'])
                ->first();
        }

        // 3. Si se pasa un hash en lugar del CSV, buscar por hash en los pasos
        if (!$circuit && strlen($inputCsv) === 64) {
            $step = \App\Models\CircuitExecutionStep::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(signature_data, '$.sha256_hash')) = ?", [$inputCsv])
                ->first();
            if ($step) {
                $circuit = $step->execution()->with(['team', 'steps.actionUser', 'steps.assignedUser', 'initiator'])->first();
                $highlightHash = $inputCsv;
                $highlightStep = $step->order;
            }
        }

        return view('circuits.public.verify', [
            'csv' => $inputCsv,
            'circuit' => $circuit,
            'searched' => true,
            'highlightHash' => $highlightHash,
            'highlightStep' => $highlightStep,
        ]);
    }

    /**
     * Descarga pública del documento oficial auténtico cotejado por CSV.
     */
    public function download(Request $request, string $csv)
    {
        $inputCsv = trim($csv);

        $circuit = CircuitExecution::where('metadata->csv', $inputCsv)
            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.csv'))) = ?", [strtolower($inputCsv)])
            ->first();

        if (!$circuit && strlen($inputCsv) === 64) {
            $step = \App\Models\CircuitExecutionStep::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(signature_data, '$.sha256_hash')) = ?", [$inputCsv])
                ->first();
            if ($step) {
                $circuit = $step->execution;
            }
        }

        if (!$circuit) {
            abort(404, 'Documento no encontrado o CSV no válido.');
        }

        $pdfService = app(\App\Services\Circuits\CircuitPdfService::class);
        $filePath = null;

        if ($request->query('type') === 'receipt') {
            $filePath = $pdfService->generateReceiptDocument($circuit);
        } else {
            $filePath = $circuit->signed_document_path;
            if (!$filePath || !Storage::disk('local')->exists($filePath)) {
                $filePath = $pdfService->generateOriginalDocument($circuit);
            }
        }

        $fileName = basename($filePath);
        return Storage::disk('local')->response(
            $filePath,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }
}
