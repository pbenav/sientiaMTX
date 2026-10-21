<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services\Circuits;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\CircuitExecution;
use App\Models\Expediente;
use App\Models\TaskAttachment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio de generación de documentos y diligencias oficiales en PDF para circuitos de tramitación.
 *
 * Genera el documento con su cuerpo principal, historial de etapas colegiadas y
 * los cajetines de firmas digitales reconocidas (PAdES / Certificado Digital).
 */
class CircuitPdfService
{
    /**
     * Genera o actualiza el documento PDF consolidado (Documento Original y Recibo de Diligencia).
     */
    public function generateAndAttach(
        CircuitExecution $execution,
        ?\App\Models\CircuitExecutionStep $signingStep = null,
        ?\App\Models\User $signingUser = null
    ): string {
        $originalPath = $this->generateOriginalDocument($execution, $signingStep, $signingUser);

        // Si ya hay pasos firmados o está aprobado, generar también el Recibo de Diligencia oficial
        if ($execution->steps()->whereNotNull('signed_at')->exists() || $signingStep !== null) {
            $this->generateReceiptDocument($execution);
        }

        return $originalPath;
    }

    /**
     * Genera el Documento Original limpio sin artificios de sistema,
     * finalizando con el contenido y el resumen de fases e itinerario para firma.
     */
    public function generateOriginalDocument(
        CircuitExecution $execution,
        ?\App\Models\CircuitExecutionStep $signingStep = null,
        ?\App\Models\User $signingUser = null
    ): string {
        $execution->loadMissing([
            'steps.assignedUser',
            'steps.assignedRole',
            'steps.actionUser',
            'circuitable',
            'initiator',
            'template',
            'team',
        ]);

        $team = $execution->team;
        $circuitable = $execution->circuitable;

        // 1. Preparar texto principal del documento en HTML seguro
        $rawContent = '';
        if ($circuitable instanceof Activity) {
            $rawContent = $circuitable->description ?? $circuitable->observations ?? '';
        } elseif ($circuitable instanceof Expediente) {
            $rawContent = $circuitable->description ?? $circuitable->notes ?? '';
        }

        // Si el contenido contiene un cierre tipo "Fin del Documento" o coletillas posteriores, recortar desde ese punto
        $rawContent = preg_replace('/(\n|\r)*(\s*---*\s*)?\*{0,2}Fin del Documento\*{0,2}.*$/si', '', $rawContent);
        $rawContent = trim($rawContent);

        $contentHtml = $rawContent
            ? $this->sanitizeEmojisForPdf((string) str($rawContent)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]))
            : '';

        $allSteps = $execution->steps()->orderBy('order', 'asc')->get();
        $simulatedExecution = $execution;

        if ($signingStep) {
            $hasRemainingSteps = $execution->steps()
                ->where('order', '>', $signingStep->order)
                ->where('status', '!=', \App\Models\CircuitExecutionStep::STATUS_APPROVED)
                ->exists();

            if (!$hasRemainingSteps) {
                $simulatedExecution = clone $execution;
                $simulatedExecution->status = CircuitExecution::STATUS_APPROVED;
            }

            $allSteps = $allSteps->map(function ($s) use ($signingStep, $signingUser) {
                if ($s->id === $signingStep->id) {
                    $clone = clone $s;
                    $clone->status = \App\Models\CircuitExecutionStep::STATUS_APPROVED;
                    if ($signingUser) {
                        $clone->setRelation('actionUser', $signingUser);
                    }
                    return $clone;
                }
                return $s;
            });
        }

        // 2. Garantizar CSV único y datos de cotejo público para la Banda Lateral Oficial
        $csv = $execution->metadata['csv'] ?? null;
        if (!$csv) {
            $meta = $execution->metadata ?? [];
            $csv = 'CSV-TRM-' . strtoupper(Str::random(4)) . '-' . sprintf('%06d', $execution->id) . '-' . strtoupper(Str::random(4));
            $meta['csv'] = $csv;
            $execution->updateQuietly(['metadata' => $meta]);
        }

        $verifyUrl = route('public.circuits.verify', $csv);
        $qrSvg = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->errorCorrection('M')->margin(0)->generate($verifyUrl));

        // Resumen de firmantes para la banda lateral
        $signedSteps = $allSteps->filter(fn($s) => $s->signed_at !== null || ($signingStep && $s->id === $signingStep->id));
        $signersList = [];
        foreach ($signedSteps as $st) {
            $sName = $st->actionUser?->name ?? $st->assignedUser?->name ?? 'Firmante';
            $sDate = $st->signed_at ? $st->signed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
            $sRole = $st->action_type_label ?? $st->name;
            $signersList[] = "{$sName} ({$sRole}) - {$sDate}";
        }
        $signersText = !empty($signersList) ? implode(' | ', $signersList) : 'En proceso de tramitación y firma';

        // 3. Renderizar vista Blade limpia del documento original con Banda Lateral
        $originalLocale = app()->getLocale();
        $docLocale = $execution->initiator?->locale ?? 'es';
        app()->setLocale($docLocale);

        try {
            $pdf = Pdf::loadView('pdf.circuit-original', [
                'execution'     => $simulatedExecution,
                'team'          => $team,
                'circuitable'   => $circuitable,
                'contentHtml'   => $contentHtml,
                'steps'         => $allSteps,
                'csv'           => $csv,
                'qrSvg'         => $qrSvg,
                'verifyUrl'     => $verifyUrl,
                'signersText'   => $signersText,
                'signingStepId' => $signingStep?->id,
                'signingUser'   => $signingUser,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'isPhpEnabled'         => true,
            ]);

            $pdfContent = $pdf->output();
        } finally {
            app()->setLocale($originalLocale);
        }

        // 3. Guardar archivo en disco local
        $directory = "circuits/{$execution->id}";
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $slugTitle = Str::slug($execution->title);
        $fileName = "Documento_{$execution->id}_{$slugTitle}.pdf";
        $filePath = "{$directory}/{$fileName}";

        Storage::disk('local')->put($filePath, $pdfContent);

        // Si aún no hay un archivo firmado por AutoFirma guardado, la ruta del documento oficial apunta aquí
        if (empty($execution->signed_document_path) || !Storage::disk('local')->exists($execution->signed_document_path)) {
            $execution->updateQuietly(['signed_document_path' => $filePath]);
        }

        // Sincronizar adjunto en Actividad o Expediente
        $this->syncAttachment($execution, $filePath, $fileName, 'Documento (Circuito #' . $execution->id . ')');

        return $filePath;
    }

    /**
     * Genera el Recibo Oficial de Tramitación y Cotejo Electrónico (Justificante con CSV y Hashes).
     */
    public function generateReceiptDocument(CircuitExecution $execution): string
    {
        $execution->loadMissing([
            'steps.assignedUser',
            'steps.assignedRole',
            'steps.actionUser',
            'circuitable',
            'initiator',
            'template',
            'team',
        ]);

        $team = $execution->team;
        $circuitable = $execution->circuitable;
        $steps = $execution->steps()->orderBy('order', 'asc')->get();

        // 1. Garantizar CSV único
        $csv = $execution->metadata['csv'] ?? null;
        if (!$csv) {
            $meta = $execution->metadata ?? [];
            $csv = 'CSV-TRM-' . strtoupper(Str::random(4)) . '-' . sprintf('%06d', $execution->id) . '-' . strtoupper(Str::random(4));
            $meta['csv'] = $csv;
            $execution->updateQuietly(['metadata' => $meta]);
        }

        // 2. Identificar el documento original firmado y calcular su huella SHA-256 real
        $signedPath = $execution->signed_document_path;
        if (!$signedPath || !Storage::disk('local')->exists($signedPath)) {
            $signedPath = $this->generateOriginalDocument($execution);
        }

        $fullDiskPath = Storage::disk('local')->path($signedPath);
        $signedDocHash = file_exists($fullDiskPath) ? hash_file('sha256', $fullDiskPath) : hash('sha256', 'CIRCUIT:' . $execution->id);
        $signedDocName = basename($signedPath);
        $signedDocSize = file_exists($fullDiskPath) ? filesize($fullDiskPath) : 0;

        // 3. Renderizar vista Blade del recibo oficial
        $originalLocale = app()->getLocale();
        $docLocale = $execution->initiator?->locale ?? 'es';
        app()->setLocale($docLocale);

        try {
            $pdf = Pdf::loadView('pdf.circuit-receipt', [
                'execution'     => $execution,
                'team'          => $team,
                'circuitable'   => $circuitable,
                'steps'         => $steps,
                'csv'           => $csv,
                'signedDocHash' => $signedDocHash,
                'signedDocName' => $signedDocName,
                'signedDocSize' => $signedDocSize,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            $pdfContent = $pdf->output();
        } finally {
            app()->setLocale($originalLocale);
        }

        // 4. Guardar recibo en disco local
        $directory = "circuits/{$execution->id}";
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $slugTitle = Str::slug($execution->title);
        $fileName = "Recibo_Tramitacion_{$execution->id}_{$slugTitle}.pdf";
        $filePath = "{$directory}/{$fileName}";

        Storage::disk('local')->put($filePath, $pdfContent);

        // Guardar referencia del recibo en metadatos
        $meta = $execution->metadata ?? [];
        $meta['receipt_document_path'] = $filePath;
        $execution->updateQuietly(['metadata' => $meta]);

        // Sincronizar adjunto del recibo
        $this->syncAttachment($execution, $filePath, $fileName, 'Recibo Oficial de Firma (Circuito #' . $execution->id . ')');

        return $filePath;
    }

    /**
     * Detecta las coordenadas exactas (en puntos PDF) del espacio reservado para la firma
     * en la última página del documento generado para AutoFirma.
     */
    public function detectSignatureCoordinates(string $relativePdfPath, int $stepIndex = 0): array
    {
        $fullPath = Storage::disk('local')->path($relativePdfPath);
        $colIndex = $stepIndex % 2;
        $rowIndex = (int) floor($stepIndex / 2);

        $defaultY = 220.0;
        $detectedY = null;

        if (file_exists($fullPath)) {
            try {
                $escaped = escapeshellarg($fullPath);
                $pyScript = "import pypdf\n"
                    . "r = pypdf.PdfReader(open({$escaped}, 'rb'))\n"
                    . "p = r.pages[-1]\n"
                    . "found = None\n"
                    . "def v(t, cm, tm, font, s):\n"
                    . "    global found\n"
                    . "    if found is None and ('AUTOFIRMA' in t or 'ESPACIO' in t):\n"
                    . "        found = tm[5]\n"
                    . "p.extract_text(visitor_text=v)\n"
                    . "if found is not None: print(found)\n";

                $output = trim((string) shell_exec("python3 -c " . escapeshellarg($pyScript)));
                if (is_numeric($output) && (float) $output > 30) {
                    $detectedY = (float) $output;
                }
            } catch (\Throwable $e) {
                // Silencioso, usar fallback
            }
        }

        $baseY = $detectedY ?? $defaultY;

        // Ajustar coordenadas para que la rúbrica encaje dentro del recuadro de la etapa
        // Margen izquierdo de 3.2cm (91pt) reservado para la banda lateral oficial
        $startX = $colIndex === 0 ? 95 : 325;
        $endX = $startX + 220;
        $yEnd = (int) round($baseY + 5 - ($rowIndex * 80));
        $yStart = (int) round($yEnd - 72);

        return [
            'startX' => $startX,
            'endX'   => $endX,
            'yStart' => max(40, $yStart),
            'yEnd'   => max(112, $yEnd),
            'page'   => -1,
        ];
    }

    /**
     * Adjunta el PDF oficial generado al elemento vinculado (Activity o Expediente).
     */
    public function syncAttachment(CircuitExecution $execution, string $filePath, string $fileName, ?string $label = null): void
    {
        $circuitable = $execution->circuitable;
        if (!$circuitable) {
            return;
        }

        $label = $label ?? ('Diligencia de Tramitación (Circuito #' . $execution->id . ')');
        $fileSize = Storage::disk('local')->exists($filePath) ? Storage::disk('local')->size($filePath) : 0;

        if ($circuitable instanceof Activity) {
            $existing = $circuitable->attachments()
                ->where('file_path', $filePath)
                ->orWhere('label', $label)
                ->first();

            if ($existing) {
                $existing->update([
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                    'disk'      => 'local',
                    'label'     => $label,
                ]);
            } else {
                $circuitable->attachments()->create([
                    'uploaded_by_id' => $execution->initiated_by_id,
                    'file_name'      => $fileName,
                    'file_path'      => $filePath,
                    'disk'           => 'local',
                    'mime_type'      => 'application/pdf',
                    'file_size'      => $fileSize,
                    'label'          => $label,
                ]);
            }
        } elseif ($circuitable instanceof Expediente) {
            $existing = TaskAttachment::where('attachable_type', Expediente::class)
                ->where('attachable_id', $circuitable->id)
                ->where('file_path', $filePath)
                ->first();

            if ($existing) {
                $existing->update([
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                ]);
            } else {
                TaskAttachment::create([
                    'attachable_type'  => Expediente::class,
                    'attachable_id'    => $circuitable->id,
                    'user_id'          => $execution->initiated_by_id,
                    'file_name'        => $fileName,
                    'file_path'        => $filePath,
                    'file_size'        => $fileSize,
                    'mime_type'        => 'application/pdf',
                    'storage_provider' => 'local',
                ]);
            }
        }
    }

    /**
     * Reemplaza emojis y caracteres gráficos no soportados por DejaVu Sans en DomPDF
     * por entidades Unicode y glifos compatibles estilizados (evita cajas vacías o 'tofu').
     */
    protected function sanitizeEmojisForPdf(string $html): string
    {
        $replacements = [
            '✅' => '<span style="color:#10b981;font-weight:bold;">&#10003;</span>',
            '✔' => '<span style="color:#10b981;font-weight:bold;">&#10003;</span>',
            '☑' => '<span style="color:#10b981;font-weight:bold;">&#10003;</span>',
            '❌' => '<span style="color:#ef4444;font-weight:bold;">&#10007;</span>',
            '✖' => '<span style="color:#ef4444;font-weight:bold;">&#10007;</span>',
            '❎' => '<span style="color:#ef4444;font-weight:bold;">&#10007;</span>',
            '⚠️' => '<span style="color:#f59e0b;font-weight:bold;">&#9888;</span>',
            '⭐' => '<span style="color:#eab308;">&#9733;</span>',
            '🔴' => '<span style="color:#ef4444;font-size:8px;">&bull;</span>',
            '🟢' => '<span style="color:#10b981;font-size:8px;">&bull;</span>',
            '🟡' => '<span style="color:#f59e0b;font-size:8px;">&bull;</span>',
            '🔵' => '<span style="color:#3b82f6;font-size:8px;">&bull;</span>',
            '➡️' => '&rarr;',
            '⬅️' => '&larr;',
            '⬆️' => '&uarr;',
            '⬇️' => '&darr;',
            '👤' => '<span style="color:#64748b;font-size:9px;">&#9787;</span>',
            '👥' => '<span style="color:#64748b;font-size:9px;">&#9787;&#9787;</span>',
            '🔒' => '<span style="color:#64748b;font-size:9px;">[Seguro]</span>',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }
}

