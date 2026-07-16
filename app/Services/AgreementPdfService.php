<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AgreementPdfService
{
    /**
     * Genera un documento PDF a partir de una actividad de tipo 'agreement'
     * y lo adjunta como ActivityAttachment.
     */
    public function generateAndAttach(Activity $activity, Team $team): ?ActivityAttachment
    {
        if ($activity->type !== 'agreement') {
            return null;
        }

        // 1. Preparar el contenido convirtiendo Markdown a HTML
        $terms = $activity->metadata['terms'] ?? $activity->description;
        $htmlContent = $terms 
            ? (string) str($terms)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) 
            : '';

        $observationsHtml = $activity->observations 
            ? (string) str($activity->observations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) 
            : '';

        // 2. Construir datos de firmantes con estado real
        $meta             = $activity->metadata ?? [];
        $memberSignatures = $meta['member_signatures'] ?? [];
        $guests           = $meta['guests'] ?? [];

        // Enriquecer member_signatures con nombre de usuario si falta
        if (!empty($memberSignatures)) {
            $usersById = $activity->assignedTo->keyBy('id');
            foreach ($memberSignatures as &$ms) {
                if (empty($ms['name']) && isset($usersById[$ms['user_id']])) {
                    $ms['name']  = $usersById[$ms['user_id']]->name;
                    $ms['email'] = $usersById[$ms['user_id']]->email ?? '';
                }
            }
            unset($ms);
        }

        // 3. Cargar la vista y generar el PDF
        $pdf = Pdf::loadView('pdf.agreement', [
            'activity'         => $activity,
            'team'             => $team,
            'content'          => $htmlContent,
            'observations'     => $observationsHtml,
            'members'          => $activity->assignedTo->pluck('name')->toArray(),
            'memberSignatures' => $memberSignatures,
            'guests'           => $guests,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        // 3. Crear el archivo base
        $fileName = 'Acuerdo_' . date('Ymd_Hi') . '_' . Str::slug($activity->title) . '.pdf';
        $directory = "activities/{$activity->id}";
        
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $filePath = "{$directory}/{$fileName}";
        
        // --- LÓGICA DE FUSIÓN CON ADJUNTOS ---
        $tempBase = tempnam(sys_get_temp_dir(), 'base_pdf');
        file_put_contents($tempBase, $pdf->output());

        // Obtener el número de páginas del documento base (donde estarán los cajetines de firma)
        $pageCountOutput = shell_exec("pdftk " . escapeshellarg($tempBase) . " dump_data | grep NumberOfPages");
        $signaturePage = -1;
        if (preg_match('/NumberOfPages: (\d+)/', $pageCountOutput, $matches)) {
            $signaturePage = (int) $matches[1];
        }

        // Buscar otros adjuntos PDF que no sean acuerdos generados
        $pdfAttachments = $activity->attachments()
            ->where('mime_type', 'application/pdf')
            ->where('file_name', 'not like', 'Acuerdo_%')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($pdfAttachments->isNotEmpty()) {
            $filesToMerge = [$tempBase];
            $tempFiles = [];
            foreach ($pdfAttachments as $att) {
                if (Storage::disk($att->disk)->exists($att->file_path)) {
                    $attContent = Storage::disk($att->disk)->get($att->file_path);
                    $tempAtt = tempnam(sys_get_temp_dir(), 'att_pdf');
                    file_put_contents($tempAtt, $attContent);
                    $filesToMerge[] = $tempAtt;
                    $tempFiles[] = $tempAtt;
                }
            }

            if (count($filesToMerge) > 1) {
                $tempMerged = tempnam(sys_get_temp_dir(), 'merged_pdf');
                $cmd = "pdftk ";
                foreach ($filesToMerge as $f) {
                    $cmd .= escapeshellarg($f) . " ";
                }
                $cmd .= "cat output " . escapeshellarg($tempMerged);
                
                shell_exec($cmd);

                if (file_exists($tempMerged) && filesize($tempMerged) > 0) {
                    Storage::disk('local')->put($filePath, file_get_contents($tempMerged));
                } else {
                    Storage::disk('local')->put($filePath, file_get_contents($tempBase));
                }
                @unlink($tempMerged);
            } else {
                Storage::disk('local')->put($filePath, file_get_contents($tempBase));
            }
            
            foreach ($tempFiles as $tf) {
                @unlink($tf);
            }
        } else {
            Storage::disk('local')->put($filePath, file_get_contents($tempBase));
        }
        @unlink($tempBase);

        // Actualizar el meta con la página de firmas
        if ($signaturePage > 0) {
            $meta = $activity->metadata ?? [];
            $meta['signature_page'] = $signaturePage;
            $activity->updateQuietly(['metadata' => $meta]);
        }
        // --- FIN LÓGICA DE FUSIÓN ---

        // 4. Registrarlo como un attachment
        $attachment = ActivityAttachment::create([
            'activity_id'    => $activity->id,
            'uploaded_by_id' => auth()->id() ?? $activity->created_by_id,
            'file_name'      => $fileName,
            'file_path'      => $filePath,
            'disk'           => 'local',
            'mime_type'      => 'application/pdf',
            'file_size'      => Storage::disk('local')->size($filePath),
        ]);

        return $attachment;
    }
}
