<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services\Circuits;

use App\Models\Activity;
use App\Models\Expediente;
use App\Models\CircuitExecution;
use App\Models\CircuitExecutionStep;
use App\Models\CircuitTemplate;
use App\Models\CircuitTemplateStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Servicio de gestión de ciclo de vida de circuitos de tramitación y aprobación.
 */
class CircuitExecutionService
{
    public function __construct(
        protected CircuitPublicationService $publicationService,
        protected CircuitPdfService $pdfService
    ) {}

    /**
     * Inicia una nueva tramitación para una Actividad o un Expediente.
     *
     * @param  Model  $circuitable  Activity o Expediente
     * @param  CircuitTemplate|null  $template  Plantilla seleccionada (opcional)
     * @param  User  $initiator  Usuario que lanza el circuito
     * @param  array  $data  Datos adicionales (título, notas, pasos ad-hoc)
     * @return CircuitExecution
     */
    public function startCircuit(Model $circuitable, ?CircuitTemplate $template, User $initiator, array $data = []): CircuitExecution
    {
        if (!($circuitable instanceof Activity) && !($circuitable instanceof Expediente)) {
            throw new InvalidArgumentException('El elemento del circuito debe ser una Actividad o un Expediente.');
        }

        // Si ya hay un circuito activo, no permitir solapar otro sin concluir
        if ($circuitable->isInCircuit) {
            throw new InvalidArgumentException('El elemento ya cuenta con un circuito de tramitación en curso.');
        }

        return DB::transaction(function () use ($circuitable, $template, $initiator, $data) {
            $title = $data['title'] ?? ($template ? $template->name . ' - ' . $circuitable->title : 'Tramitación: ' . $circuitable->title);

            $execution = CircuitExecution::create([
                'team_id' => $circuitable->team_id,
                'circuit_template_id' => $template?->id,
                'circuitable_type' => get_class($circuitable),
                'circuitable_id' => $circuitable->id,
                'title' => $title,
                'initiated_by_id' => $initiator->id,
                'status' => CircuitExecution::STATUS_IN_PROGRESS,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'started_at' => now(),
            ]);

            if ($template && $template->steps->isNotEmpty()) {
                foreach ($template->steps as $tStep) {
                    $assignedUserId = null;
                    $assignedRoleId = null;

                    switch ($tStep->assignee_type) {
                        case CircuitTemplateStep::ASSIGNEE_SPECIFIC_USER:
                            $assignedUserId = $tStep->assignee_id;
                            break;
                        case CircuitTemplateStep::ASSIGNEE_TEAM_ROLE:
                            $assignedRoleId = $tStep->assignee_id;
                            break;
                        case CircuitTemplateStep::ASSIGNEE_CREATOR:
                            $assignedUserId = $circuitable->created_by_id ?? $circuitable->creator?->id ?? $initiator->id;
                            break;
                        case CircuitTemplateStep::ASSIGNEE_RESPONSIBLE:
                            if ($circuitable instanceof Activity) {
                                $assignedUserId = $circuitable->assignedTo->first()?->id ?? $circuitable->created_by_id;
                            } else {
                                $assignedUserId = $circuitable->assigned_user_id ?? $circuitable->created_by_id;
                            }
                            break;
                        case CircuitTemplateStep::ASSIGNEE_DYNAMIC:
                            // Si se especificó en data['assignees'][order]
                            $assignedUserId = $data['assignees'][$tStep->order] ?? null;
                            break;
                    }

                    CircuitExecutionStep::create([
                        'circuit_execution_id' => $execution->id,
                        'template_step_id' => $tStep->id,
                        'order' => $tStep->order,
                        'name' => $tStep->name,
                        'description' => $tStep->description,
                        'action_type' => $tStep->action_type,
                        'assignee_type' => $tStep->assignee_type,
                        'assigned_to_user_id' => $assignedUserId,
                        'assigned_to_role_id' => $assignedRoleId,
                        'status' => CircuitExecutionStep::STATUS_PENDING,
                        'requires_signature' => $tStep->requires_signature ?? false,
                        'action_data' => $tStep->action_config ?? [],
                    ]);
                }
            } elseif (!empty($data['steps'])) {
                // Pasos personalizados ad-hoc
                foreach ($data['steps'] as $index => $stepData) {
                    CircuitExecutionStep::create([
                        'circuit_execution_id' => $execution->id,
                        'template_step_id' => null,
                        'order' => $stepData['order'] ?? ($index + 1),
                        'name' => $stepData['name'],
                        'description' => $stepData['description'] ?? null,
                        'action_type' => $stepData['action_type'] ?? CircuitTemplateStep::ACTION_APPROVAL,
                        'assignee_type' => $stepData['assignee_type'] ?? CircuitTemplateStep::ASSIGNEE_SPECIFIC_USER,
                        'assigned_to_user_id' => $stepData['assigned_to_user_id'] ?? null,
                        'assigned_to_role_id' => $stepData['assigned_to_role_id'] ?? null,
                        'status' => CircuitExecutionStep::STATUS_PENDING,
                        'requires_signature' => $stepData['requires_signature'] ?? false,
                        'action_data' => $stepData['action_config'] ?? [],
                    ]);
                }
            } else {
                // Flujo estándar por defecto (Visto Bueno -> Justificación -> Aprobación)
                $defaultSteps = [
                    [
                        'order' => 1,
                        'name' => 'Visto Bueno Inicial',
                        'description' => 'Revisión preliminar y conformidad inicial con la propuesta.',
                        'action_type' => CircuitTemplateStep::ACTION_ENDORSEMENT,
                        'assignee_type' => CircuitTemplateStep::ASSIGNEE_CREATOR,
                        'assigned_to_user_id' => $circuitable->created_by_id ?? $initiator->id,
                        'requires_signature' => false,
                    ],
                    [
                        'order' => 2,
                        'name' => 'Justificación Técnica',
                        'description' => 'Acreditación y justificación técnica o de necesidad de la actuación.',
                        'action_type' => CircuitTemplateStep::ACTION_JUSTIFICATION,
                        'assignee_type' => CircuitTemplateStep::ASSIGNEE_RESPONSIBLE,
                        'assigned_to_user_id' => $circuitable instanceof Activity ? ($circuitable->assignedTo->first()?->id ?? $initiator->id) : ($circuitable->assigned_user_id ?? $initiator->id),
                        'requires_signature' => false,
                    ],
                    [
                        'order' => 3,
                        'name' => 'Aprobación Final',
                        'description' => 'Aprobación y firma definitiva por parte de la coordinación o responsable.',
                        'action_type' => CircuitTemplateStep::ACTION_APPROVAL,
                        'assignee_type' => CircuitTemplateStep::ASSIGNEE_SPECIFIC_USER,
                        'assigned_to_user_id' => null,
                        'requires_signature' => true,
                    ],
                ];

                foreach ($defaultSteps as $stepDef) {
                    CircuitExecutionStep::create(array_merge([
                        'circuit_execution_id' => $execution->id,
                        'template_step_id' => null,
                        'status' => CircuitExecutionStep::STATUS_PENDING,
                        'action_data' => [],
                    ], $stepDef));
                }
            }

            // Fijar el primer paso como el paso actual
            $firstStep = $execution->steps()->orderBy('order', 'asc')->first();
            if ($firstStep) {
                $execution->update(['current_step_id' => $firstStep->id]);
            }

            return $execution->fresh(['steps', 'currentStep', 'circuitable']);
        });
    }

    /**
     * Procesa una decisión sobre el paso actual de la tramitación.
     *
     * @param  CircuitExecutionStep  $step
     * @param  User  $user
     * @param  string  $decision  ('approved', 'rejected', 'returned')
     * @param  string|null  $comments
     * @param  array  $additionalData
     * @return CircuitExecution
     */
    public function processStep(
        CircuitExecutionStep $step,
        User $user,
        string $decision,
        ?string $comments = null,
        array $additionalData = []
    ): CircuitExecution {
        $execution = $step->execution;

        if (!$execution || !$execution->is_active) {
            throw new InvalidArgumentException('Esta tramitación no se encuentra activa.');
        }

        if ($execution->current_step_id !== $step->id) {
            throw new InvalidArgumentException('Solo se puede actuar sobre el paso en curso de la tramitación.');
        }

        if (!$step->canUserAct($user, $execution)) {
            throw new InvalidArgumentException('No tienes permisos asignados para resolver este paso.');
        }

        return DB::transaction(function () use ($step, $execution, $user, $decision, $comments, $additionalData) {
            $auditData = array_merge($step->action_data ?? [], [
                'resolved_by_ip' => request()->ip() ?? '127.0.0.1',
                'resolved_by_agent' => request()->userAgent() ?? 'System',
                'resolved_at' => now()->toIso8601String(),
            ], $additionalData);

            $step->update([
                'action_taken_by_id' => $user->id,
                'decision' => $decision,
                'comments' => $comments,
                'action_data' => $auditData,
                'completed_at' => now(),
            ]);

            if ($decision === CircuitExecutionStep::DECISION_APPROVED) {
                // Registrar firma digital si el paso la requiere o si el usuario firma en este paso
                $isSigning = $step->requires_signature || !empty($additionalData['sign_now']) || !empty($additionalData['signature_data']);
                if ($isSigning) {
                    $sigMethod = $additionalData['signature_method'] ?? 'internal_certificate';
                    $csv = $execution->metadata['csv'] ?? '';
                    $evidencePayload = implode('|', [
                        'CIRCUIT:' . $execution->id,
                        'STEP:' . $step->id,
                        'ORDER:' . $step->order,
                        'USER:' . $user->id,
                        'EMAIL:' . $user->email,
                        'TS:' . now()->toIso8601String(),
                        'CSV:' . $csv,
                        'COMMENTS:' . ($comments ?? ''),
                    ]);
                    $calcHash = hash('sha256', $evidencePayload);

                    $sigData = array_merge([
                        'method' => $sigMethod,
                        'signer_name' => $user->name,
                        'signer_email' => $user->email,
                        'algorithm' => $additionalData['signature_algorithm'] ?? 'SHA256withRSA',
                        'certificate_subject' => $additionalData['certificate_subject'] ?? $user->name,
                        'sha256_hash' => $additionalData['signature_data']['sha256_hash'] ?? $calcHash,
                        'ip' => request()->ip() ?? '127.0.0.1',
                        'user_agent' => request()->userAgent() ?? 'System',
                        'timestamp' => now()->toIso8601String(),
                    ], $additionalData['signature_data'] ?? []);

                    $step->update([
                        'signed_at' => now(),
                        'signature_data' => $sigData,
                    ]);
                }

                $step->update(['status' => CircuitExecutionStep::STATUS_APPROVED]);

                // Si este paso era de tipo publicación y tiene auto-publicar activado
                if ($step->action_type === CircuitTemplateStep::ACTION_PUBLICATION) {
                    $this->publicationService->publish($execution, $user, [
                        'summary' => $comments ?? $execution->title,
                    ]);
                }

                // Buscar siguiente paso
                $nextStep = $execution->steps()
                    ->where('order', '>', $step->order)
                    ->where('status', '!=', CircuitExecutionStep::STATUS_APPROVED)
                    ->orderBy('order', 'asc')
                    ->first();

                if ($nextStep) {
                    $execution->update([
                        'current_step_id' => $nextStep->id,
                        'status' => CircuitExecution::STATUS_IN_PROGRESS,
                    ]);
                } else {
                    // Todos los pasos han sido completados con éxito
                    $execution->update([
                        'status' => CircuitExecution::STATUS_APPROVED,
                        'completed_at' => now(),
                    ]);

                    // Si la plantilla incluye publicación automática al finalizar y no se ha publicado aún
                    $hasPublicationStep = $execution->steps()->where('action_type', CircuitTemplateStep::ACTION_PUBLICATION)->exists();
                    if (!$hasPublicationStep && ($execution->template?->settings['auto_publish'] ?? false)) {
                        $this->publicationService->publish($execution, $user);
                    }
                }

                // Generar o actualizar el documento consolidado en PDF con la nueva firma
                try {
                    $signedFilePath = $additionalData['signature_data']['signed_file_path'] ?? null;
                    if ($signedFilePath && \Illuminate\Support\Facades\Storage::disk('local')->exists($signedFilePath)) {
                        $execution->updateQuietly(['signed_document_path' => $signedFilePath]);
                        $this->pdfService->syncAttachment($execution, $signedFilePath, basename($signedFilePath));
                    } else {
                        $this->pdfService->generateAndAttach($execution);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Circuit PDF generation failed: ' . $e->getMessage());
                }
            } elseif ($decision === CircuitExecutionStep::DECISION_RETURNED) {
                // Devolver para subsanación
                $step->update(['status' => CircuitExecutionStep::STATUS_RETURNED]);

                // Determinar a qué paso devolver (por defecto al paso 1 o al anterior)
                $returnToOrder = $additionalData['return_to_order'] ?? 1;
                $targetStep = $execution->steps()->where('order', $returnToOrder)->first()
                    ?? $execution->steps()->orderBy('order', 'asc')->first();

                $execution->update([
                    'current_step_id' => $targetStep?->id ?? $step->id,
                    'status' => CircuitExecution::STATUS_RETURNED,
                ]);

                // Poner el paso de destino nuevamente en pending
                if ($targetStep) {
                    $targetStep->update(['status' => CircuitExecutionStep::STATUS_PENDING]);
                }
            } elseif ($decision === CircuitExecutionStep::DECISION_REJECTED) {
                // Rechazo / Denegación definitiva
                $step->update(['status' => CircuitExecutionStep::STATUS_REJECTED]);

                $execution->update([
                    'status' => CircuitExecution::STATUS_REJECTED,
                    'completed_at' => now(),
                ]);
            }

            return $execution->fresh(['steps', 'currentStep', 'publication']);
        });
    }

    /**
     * Cancela una tramitación en curso.
     */
    public function cancelCircuit(CircuitExecution $execution, User $user, string $reason): CircuitExecution
    {
        if (!$execution->is_active) {
            throw new InvalidArgumentException('Solo se pueden cancelar tramitaciones activas.');
        }

        $execution->update([
            'status' => CircuitExecution::STATUS_CANCELLED,
            'completed_at' => now(),
            'notes' => trim(($execution->notes ? $execution->notes . "\n" : '') . "Cancelado por {$user->name}: {$reason}"),
        ]);

        return $execution->fresh();
    }

    /**
     * Elimina definitivamente / purga una tramitación de prueba o errónea (Soft Delete).
     * Esta acción está restringida exclusivamente a Administradores.
     */
    public function deleteCircuit(CircuitExecution $execution, User $user): void
    {
        DB::transaction(function () use ($execution, $user) {
            // Retirar publicación en tablón si la hubiera
            if ($execution->publication) {
                $execution->publication->delete();
            }

            // Eliminar etapas del circuito
            $execution->steps()->delete();

            // Eliminar archivo PDF generado en disco si existe
            if ($execution->signed_document_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($execution->signed_document_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($execution->signed_document_path);
            }

            // Soft-delete de la ejecución
            $execution->delete();

            \Illuminate\Support\Facades\Log::info("Circuito #{$execution->id} ('{$execution->title}') eliminado por el administrador {$user->name} ({$user->email}).");
        });
    }
}
