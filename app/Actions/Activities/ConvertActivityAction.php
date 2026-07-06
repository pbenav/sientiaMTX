<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\User;
use App\Services\TemplateLoader;
use App\Services\ActivityTemplateValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConvertActivityAction
{
    public function __construct(
        protected TemplateLoader $templateLoader,
        protected ActivityTemplateValidator $validator
    ) {}
    /**
     * Convierte una actividad de un tipo a otro, aplicando inmutabilidad por deprecación
     * y clonación relacional en cascada para actividades hijas (Planes Maestros).
     *
     * @param Activity $source Actividad origen a convertir
     * @param string $newType Nuevo tipo (ej. 'task', 'meeting', 'document')
     * @param User $actor Usuario que realiza la acción
     * @param ?int $newParentId ID del nuevo padre si es una conversión recursiva de hijos
     * @return array Resultado de la operación ['success' => bool, 'message' => string, 'activity' => ?Activity]
     */
    public function execute(Activity $source, string $newType, User $actor, ?int $newParentId = null): array
    {
        // 1. Verificación de permisos y autorización (abierto a asignados, creador y admins)
        if (!$this->canConvert($source, $actor)) {
            return [
                'success' => false,
                'message' => __('No tienes permisos suficientes para convertir esta actividad. Debes ser creador, estar asignado o ser administrador.'),
                'activity' => null
            ];
        }

        // 2. Validación de tipo destino
        if (!array_key_exists($newType, Activity::SUBTYPES)) {
            return [
                'success' => false,
                'message' => __('El tipo de actividad destino no es válido.'),
                'activity' => null
            ];
        }

        if ($source->type === $newType && $newParentId === null) {
            return [
                'success' => false,
                'message' => __('La actividad ya es del tipo solicitado.'),
                'activity' => null
            ];
        }

        try {
            return DB::transaction(function () use ($source, $newType, $actor, $newParentId) {
                // 3. Crear la nueva actividad (Clon Base)
                $newUuid = Str::uuid()->toString();
                
                $sourceMetadata = $source->metadata ?? [];
                
                // Mapeo inteligente con TemplateLoader
                $targetMetadata = [];
                $template = $this->templateLoader->getTemplate($newType);

                if ($template && isset($template['properties'])) {
                    foreach ($template['properties'] as $key => $rules) {
                        if (array_key_exists($key, $sourceMetadata)) {
                            $val = $sourceMetadata[$key];
                            // Forzar default si el valor no entra en los permitidos del nuevo enum
                            if (isset($rules['enum']) && !in_array($val, $rules['enum'], true)) {
                                $val = $rules['default'] ?? null;
                            }
                            $targetMetadata[$key] = $val;
                        } else {
                            $targetMetadata[$key] = $rules['default'] ?? null;
                        }
                    }
                } else {
                    // Fallback si no hay template
                    $targetMetadata = $sourceMetadata;
                }

                // Heredar estado base y adaptarlo a las reglas del nuevo tipo
                $currentStatusValue = $source->status['value'] ?? 'pending';
                $allowedStatuses = $template['properties']['status']['enum'] ?? null;
                $defaultStatus = $template['properties']['status']['default'] ?? 'pending';

                if ($allowedStatuses !== null && !in_array($currentStatusValue, $allowedStatuses, true)) {
                    if ($currentStatusValue === 'pending' && in_array('scheduled', $allowedStatuses, true)) {
                        $currentStatusValue = 'scheduled';
                    } elseif ($currentStatusValue === 'scheduled' && in_array('pending', $allowedStatuses, true)) {
                        $currentStatusValue = 'pending';
                    } else {
                        $currentStatusValue = $defaultStatus;
                    }
                }
                
                $targetStatus = ['value' => $currentStatusValue];

                // Validamos los nuevos metadatos incluyendo los campos base para satisfacer al TemplateValidator
                $dataForValidation = array_merge($targetMetadata, [
                    'title' => $source->title,
                    'description' => $source->description,
                    'status' => $currentStatusValue,
                ]);
                $this->validator->validate($newType, $dataForValidation);

                $targetMetadata['converted_from_uuid'] = $source->uuid;
                $targetMetadata['converted_at'] = now()->toDateTimeString();
                $targetMetadata['conversion_actor_id'] = $actor->id;

                $target = Activity::create([
                    'uuid' => $newUuid,
                    'team_id' => $source->team_id,
                    'created_by_id' => $source->created_by_id, // Mantenemos el creador original
                    'parent_id' => $newParentId ?? $source->parent_id, // Asignamos el nuevo padre si estamos en cascada
                    'expediente_id' => $source->expediente_id,
                    'type' => $newType,
                    'title' => $source->title,
                    'description' => $source->description,
                    'status' => $targetStatus,
                    'metadata' => $targetMetadata,
                    'visibility' => $source->visibility,
                    'due_date' => $source->due_date,
                    'scheduled_date' => $source->scheduled_date,
                    'original_due_date' => $source->original_due_date,
                    'priority' => $source->priority,
                    'auto_priority' => $source->auto_priority,
                    'progress_percentage' => $source->progress_percentage,
                    // No clonamos el kanban_column_id para que se autoubique en su nuevo tablero si procede
                    'is_archived' => false,
                    'is_template' => $source->is_template,
                ]);

                // 4. Replicación de Relaciones
                // 4.1 Asignaciones
                foreach ($source->assignments as $assignment) {
                    $target->assignments()->create([
                        'user_id' => $assignment->user_id,
                        'group_id' => $assignment->group_id,
                        'assigned_by_id' => $assignment->assigned_by_id ?? $actor->id,
                        'assigned_at' => $assignment->assigned_at ?? now(),
                    ]);
                }

                // 4.2 Etiquetas
                foreach ($source->tags as $tag) {
                    $target->tags()->create([
                        'tag_id' => $tag->tag_id,
                    ]);
                }

                // 4.3 Notas
                foreach ($source->notes as $note) {
                    $target->notes()->create([
                        'user_id' => $note->user_id,
                        'content' => $note->content,
                        'visibility' => $note->visibility ?? 'public',
                    ]);
                }

                // 4.4 Adjuntos
                foreach ($source->attachments as $attachment) {
                    $target->attachments()->create([
                        'user_id' => $attachment->user_id,
                        'name' => $attachment->name,
                        'file_path' => $attachment->file_path,
                        'file_type' => $attachment->file_type,
                        'file_size' => $attachment->file_size,
                    ]);
                }

                // 4.5 Historial inicial para el nuevo registro
                $target->histories()->create([
                    'user_id' => $actor->id,
                    'action' => 'converted_from',
                    'details' => json_encode([
                        'from_activity_id' => $source->id,
                        'from_uuid' => $source->uuid,
                        'from_type' => $source->type,
                        'note' => 'Creada mediante conversión de actividad original'
                    ])
                ]);

                // 5. Ocultación y Deprecación del Origen
                $sourceMetadata = $source->metadata ?? [];
                $sourceMetadata['converted_to_uuid'] = $target->uuid;
                $sourceMetadata['converted_to_id'] = $target->id;
                $sourceMetadata['is_deprecated'] = true;

                $sourceStatus = [
                    'value' => 'deprecated',
                    'reason' => 'converted',
                    'converted_to_uuid' => $target->uuid,
                    'converted_at' => now()->toDateTimeString()
                ];

                $source->is_archived = true;
                $source->status = $sourceStatus;
                $source->metadata = $sourceMetadata;
                $source->saveQuietly();

                // 5.1 Ocultación de la Tarea Legacy si existe el mapeo y re-enlace de Citas (Appointments)
                // Primero actualizamos cualquier Cita que ya estuviera apuntando a esta actividad origen
                \App\Models\Appointment::where('activity_id', $source->id)->update(['activity_id' => $target->id]);

                if ($source->type === 'task') {
                    $mapping = DB::table('activity_task_mapping')->where('activity_id', $source->id)->first();
                    if ($mapping) {
                        $legacyTask = \App\Models\Task::find($mapping->task_id);
                        if ($legacyTask) {
                            // Si la tarea legacy tenía una cita asociada, redirigimos la cita a la nueva actividad
                            \App\Models\Appointment::where('task_id', $legacyTask->id)
                                ->update(['activity_id' => $target->id]);

                            $legacyTask->is_archived = true;
                            $legacyTask->status = 'cancelled';
                            $legacyTask->saveQuietly();
                            // Cancelamos timers abiertos si los hubiera
                            $legacyTask->timeLogs()->whereNull('end_at')->update(['end_at' => now()]);
                        }
                    }
                }

                // Registrar en historial del origen
                $source->histories()->create([
                    'user_id' => $actor->id,
                    'action' => 'deprecated_by_conversion',
                    'details' => json_encode([
                        'to_activity_id' => $target->id,
                        'to_uuid' => $target->uuid,
                        'to_type' => $target->type,
                    ])
                ]);

                // 6. TRATAMIENTO EN CASCADA DE ACTIVIDADES HIJAS (Planes Maestros)
                // Si la actividad tiene hijos, los procesamos para que sigan la misma suerte
                if ($source->children()->exists()) {
                    foreach ($source->children as $child) {
                        // Convierten en cascada apuntando al nuevo ID de la maestra ($target->id)
                        // De esta forma, el sub-árbol original queda totalmente oculto y deprecado,
                        // y el nuevo sub-árbol nace limpio bajo la nueva actividad maestra.
                        $this->execute($child, $newType, $actor, $target->id);
                    }
                }

                // 7. Notificaciones a los asignados (solo para la actividad raíz para no hacer spam con los hijos)
                if ($newParentId === null) {
                    $this->notifyUsers($target, $source, $actor);
                }

                Log::info("Actividad {$source->id} ({$source->type}) convertida a {$target->id} ({$target->type}) por usuario {$actor->id}");

                return [
                    'success' => true,
                    'message' => __('Actividad convertida correctamente a :type.', ['type' => $target->type_label]),
                    'activity' => $target
                ];
            });
        } catch (\Exception $e) {
            Log::error("Fallo al convertir la actividad {$source->id}: " . $e->getMessage(), ['exception' => $e]);
            return [
                'success' => false,
                'message' => __('Ha ocurrido un error inesperado al convertir la actividad: ' . $e->getMessage()),
                'activity' => null
            ];
        }
    }

    /**
     * Verifica si el usuario tiene permisos para convertir la actividad.
     * Abierto al creador, usuarios asignados, coordinadores y administradores.
     */
    protected function canConvert(Activity $activity, User $user): bool
    {
        // 1. Es el creador de la actividad
        if ($activity->created_by_id === $user->id) {
            return true;
        }

        // 2. Es uno de los usuarios asignados a la actividad
        if ($activity->assignedTo->contains('id', $user->id)) {
            return true;
        }

        // 3. Pertenece a un grupo asignado a la actividad
        if ($activity->assignedGroups->filter(fn($g) => $g->users->contains('id', $user->id))->isNotEmpty()) {
            return true;
        }

        // 4. Es administrador del sistema
        if ($user->is_admin) {
            return true;
        }

        // 5. Es coordinador del equipo al que pertenece la actividad
        if ($activity->team && $activity->team->isCoordinator($user)) {
            return true;
        }

        return false;
    }

    /**
     * Envía notificaciones a los usuarios asignados sobre el cambio de tipo de actividad.
     */
    protected function notifyUsers(Activity $target, Activity $source, User $actor): void
    {
        $recipients = $target->assignedTo->filter(fn($u) => $u->id !== $actor->id);

        foreach ($recipients as $recipient) {
            // Log de notificación
            Log::info("Notificando a usuario {$recipient->id} sobre la conversión de la actividad {$target->id}");
        }
    }
}
