<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services\Circuits;

use App\Models\CircuitExecution;
use App\Models\CircuitPublication;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Servicio para la publicación de documentos y acuerdos aprobados en el Tablón Edictal / Portal Público.
 */
class CircuitPublicationService
{
    /**
     * Publica una tramitación finalizada en el Tablón Edictal / Portal Ciudadano.
     *
     * @param  CircuitExecution  $execution
     * @param  User  $publisher
     * @param  array  $data
     * @return CircuitPublication
     */
    public function publish(CircuitExecution $execution, User $publisher, array $data = []): CircuitPublication
    {
        $circuitable = $execution->circuitable;

        // 1. Preparar contenido: convertir Markdown a HTML seguro si es necesario
        $rawContent = $data['content_html'] ?? '';
        if (empty($rawContent)) {
            if ($circuitable instanceof \App\Models\Activity) {
                $rawContent = $circuitable->description ?? $circuitable->observations ?? '';
            } elseif ($circuitable instanceof \App\Models\Expediente) {
                $rawContent = $circuitable->description ?? $circuitable->notes ?? '';
            }
        }

        $contentHtml = !empty($rawContent)
            ? (string) str($rawContent)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false])
            : '';

        // 2. Asegurar que el documento PDF oficial consolidado esté generado con todas sus firmas
        $pdfService = app(\App\Services\Circuits\CircuitPdfService::class);
        $pdfPath = $execution->signed_document_path;
        if (!$pdfPath || !\Illuminate\Support\Facades\Storage::disk('local')->exists($pdfPath)) {
            $pdfPath = $pdfService->generateAndAttach($execution);
        }

        // Si ya existe una publicación previa, actualizarla
        if ($execution->publication) {
            $execution->publication->update([
                'title' => $data['title'] ?? $execution->title,
                'summary' => $data['summary'] ?? $execution->publication->summary,
                'status' => CircuitPublication::STATUS_PUBLISHED,
                'publication_date' => $data['publication_date'] ?? now()->toDateString(),
                'expiration_date' => $data['expiration_date'] ?? $execution->publication->expiration_date,
                'content_html' => $contentHtml ?: $execution->publication->content_html,
                'metadata' => array_merge($execution->publication->metadata ?? [], [
                    'signed_pdf_path' => $pdfPath,
                    'csv' => $execution->metadata['csv'] ?? null,
                ]),
            ]);

            return $execution->publication;
        }

        // Generar código oficial de documento / edicto
        $year = now()->format('Y');
        $count = CircuitPublication::where('team_id', $execution->team_id)
            ->whereYear('created_at', $year)
            ->count() + 1;
        $documentCode = sprintf('PUB-%s-%04d', $year, $count);

        // Recopilar diligencia de firmas de los pasos del circuito
        $signatures = $execution->steps()
            ->with(['actionUser'])
            ->whereNotNull('completed_at')
            ->get()
            ->map(function ($step) {
                return [
                    'step_order' => $step->order,
                    'step_name' => $step->name,
                    'action_type' => $step->action_type,
                    'signer_name' => $step->actionUser?->name ?? 'Usuario del sistema',
                    'decision' => $step->decision,
                    'completed_at' => $step->completed_at?->toIso8601String(),
                    'comments' => $step->comments,
                ];
            })
            ->toArray();

        $publication = CircuitPublication::create([
            'team_id' => $execution->team_id,
            'circuit_execution_id' => $execution->id,
            'published_by_id' => $publisher->id,
            'title' => $data['title'] ?? $execution->title,
            'document_code' => $documentCode,
            'summary' => $data['summary'] ?? ($circuitable?->description ? Str::limit(strip_tags($circuitable->description), 280) : null),
            'channel' => $data['channel'] ?? CircuitPublication::CHANNEL_INTERNAL,
            'status' => CircuitPublication::STATUS_PUBLISHED,
            'public_slug' => Str::slug($data['title'] ?? $execution->title) . '-' . Str::lower(Str::random(8)),
            'public_token' => Str::random(32),
            'publication_date' => $data['publication_date'] ?? now()->toDateString(),
            'expiration_date' => $data['expiration_date'] ?? now()->addDays(30)->toDateString(), // Por defecto 30 días en tablón
            'content_html' => $contentHtml,
            'attachments' => $data['attachments'] ?? [],
            'metadata' => [
                'signatures_audit' => $signatures,
                'circuitable_type' => $execution->circuitable_type,
                'circuitable_id'   => $execution->circuitable_id,
                'signed_pdf_path'  => $pdfPath,
                'csv'              => $execution->metadata['csv'] ?? null,
            ],
        ]);

        $execution->update(['status' => CircuitExecution::STATUS_PUBLISHED]);

        return $publication;
    }

    /**
     * Retira formalmente una publicación del tablón público.
     */
    public function withdraw(CircuitPublication $publication, User $user, string $reason): CircuitPublication
    {
        $publication->update([
            'status' => CircuitPublication::STATUS_WITHDRAWN,
            'metadata' => array_merge($publication->metadata ?? [], [
                'withdrawn_by_id' => $user->id,
                'withdrawn_at' => now()->toIso8601String(),
                'withdraw_reason' => $reason,
            ]),
        ]);

        return $publication;
    }
}

