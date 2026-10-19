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
     * Genera o actualiza el documento PDF consolidado con todas las firmas del circuito.
     */
    public function generateAndAttach(CircuitExecution $execution): string
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

        // 1. Preparar texto principal del documento en HTML seguro
        $rawContent = '';
        if ($circuitable instanceof Activity) {
            $rawContent = $circuitable->description ?? $circuitable->observations ?? '';
        } elseif ($circuitable instanceof Expediente) {
            $rawContent = $circuitable->description ?? $circuitable->notes ?? '';
        }

        $contentHtml = $rawContent 
            ? (string) str($rawContent)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) 
            : '';

        // 2. Generar Código Seguro de Verificación (CSV) si no existe
        $csv = $execution->metadata['csv'] ?? null;
        if (!$csv) {
            $meta = $execution->metadata ?? [];
            $csv = 'CSV-TRM-' . strtoupper(Str::random(4)) . '-' . sprintf('%06d', $execution->id) . '-' . strtoupper(Str::random(4));
            $meta['csv'] = $csv;
            $execution->updateQuietly(['metadata' => $meta]);
        }

        // 3. Renderizar la vista Blade del documento oficial
        $pdf = Pdf::loadView('pdf.circuit', [
            'execution'   => $execution,
            'team'        => $team,
            'circuitable' => $circuitable,
            'contentHtml' => $contentHtml,
            'steps'       => $execution->steps()->orderBy('order', 'asc')->get(),
            'csv'         => $csv,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        // 4. Guardar archivo en almacenamiento local
        $directory = "circuits/{$execution->id}";
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $slugTitle = Str::slug($execution->title);
        $fileName = "Tramitacion_{$execution->id}_{$slugTitle}_Firmado.pdf";
        $filePath = "{$directory}/{$fileName}";

        Storage::disk('local')->put($filePath, $pdf->output());

        // 5. Actualizar la referencia en la ejecución
        $execution->updateQuietly(['signed_document_path' => $filePath]);

        // 6. Sincronizar como adjunto en el elemento vinculado (Actividad o Expediente)
        $this->syncAttachment($execution, $filePath, $fileName);

        return $filePath;
    }

    /**
     * Adjunta el PDF oficial generado al elemento vinculado (Activity o Expediente).
     */
    protected function syncAttachment(CircuitExecution $execution, string $filePath, string $fileName): void
    {
        $circuitable = $execution->circuitable;
        if (!$circuitable) {
            return;
        }

        $fileSize = Storage::disk('local')->exists($filePath) ? Storage::disk('local')->size($filePath) : 0;

        if ($circuitable instanceof Activity) {
            $existing = $circuitable->attachments()
                ->where('file_path', $filePath)
                ->orWhere('label', 'like', 'Diligencia de Tramitación (Circuito #' . $execution->id . ')%')
                ->first();

            if ($existing) {
                $existing->update([
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                    'disk'      => 'local',
                ]);
            } else {
                $circuitable->attachments()->create([
                    'uploaded_by_id' => $execution->initiated_by_id,
                    'file_name'      => $fileName,
                    'file_path'      => $filePath,
                    'disk'           => 'local',
                    'mime_type'      => 'application/pdf',
                    'file_size'      => $fileSize,
                    'label'          => 'Diligencia de Tramitación (Circuito #' . $execution->id . ')',
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
}
