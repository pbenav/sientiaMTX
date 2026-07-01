<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Factories;

use App\Models\Team;
use App\Models\Activity;
use App\Models\Skill;
use App\Contracts\ExportableActivityInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Factoría universal para la importación y exportación de Actividades.
 * Orquesta la creación dinámica de subtipos basándose en el esquema v2,
 * manteniendo soporte legacy para v1.
 */
class ActivityFactory
{
    /**
     * Crea una actividad polimórfica a partir de un contenido JSON.
     *
     * @param Team $team
     * @param string $jsonContent
     * @return Activity
     * @throws \Exception
     */
    public function makeFromJson(Team $team, string $jsonContent): Activity
    {
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('ActivityFactory: JSON decodificación fallida - ' . json_last_error_msg());
            throw new \InvalidArgumentException(__('Formato de datos JSON inválido.'));
        }

        // ─── 1. Capa de Adaptación Legacy (v1 -> v2) ──────────────────────────
        $version = $data['version'] ?? $data['type'] ?? 'unknown';

        if ($version === 'sientia_task_v1') {
            $taskData = $data['task'] ?? [];
            $core = [
                'type'                 => $taskData['type'] ?? 'task',
                'title'                => $taskData['title'] ?? __('Sin título'),
                'description'          => $taskData['description'] ?? null,
                'priority'             => $taskData['priority'] ?? 'medium',
                'urgency'              => $taskData['urgency'] ?? 'medium',
                'visibility'           => $taskData['visibility'] ?? 'private',
                'is_template'          => $taskData['is_template'] ?? false,
                'skills'               => $taskData['skills'] ?? [],
                'tags'                 => $taskData['tags'] ?? [],
            ];
            // En v1, las specs estaban dispersas en la raíz
            $specs = [
                'cognitive_load'       => $taskData['cognitive_load'] ?? 1,
                'is_backstage'         => $taskData['is_backstage'] ?? false,
                'autoprogram_settings' => $taskData['autoprogram_settings'] ?? null,
                'is_out_of_skill_tree' => $taskData['is_out_of_skill_tree'] ?? false,
            ];
        } elseif ($version === 'sientia_activity_v2') {
            $core = $data['core'] ?? [];
            $specs = $data['specs'] ?? [];
        } else {
            Log::warning('ActivityFactory: Versión de esquema no soportada: ' . $version);
            throw new \InvalidArgumentException(__('Versión de esquema JSON no soportada.'));
        }

        // Validación estricta del $core (Seguridad)
        $coreValidator = \Illuminate\Support\Facades\Validator::make($core, [
            'type'           => 'required|string|max:50',
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000000',
            'priority'       => 'nullable|string|in:low,medium,high,critical',
            'urgency'        => 'nullable|string|in:low,medium,high,critical',
            'visibility'     => 'nullable|string|in:public,private,semiprivate,team',
            'is_template'    => 'nullable|boolean',
            'status'         => 'nullable|array',
            'due_date'       => 'nullable|date',
            'scheduled_date' => 'nullable|date',
            'skills'         => 'nullable|array',
            'tags'           => 'nullable|array',
        ]);

        if ($coreValidator->fails()) {
            throw new \InvalidArgumentException('Datos principales (core) inválidos: ' . implode(', ', $coreValidator->errors()->all()));
        }

        $type = $core['type'] ?? 'task';

        // ─── 2. Transacción de Creación y Asignación de Specs ────────────────
        return DB::transaction(function () use ($team, $core, $specs, $type, $version) {
            $user = auth()->user();
            $userId = $user ? $user->id : null;

            // Instanciar el modelo concreto o base
            $modelClass = Activity::SUBTYPES[$type] ?? Activity::class;
            
            /** @var Activity $activity */
            $activity = new $modelClass();
            $activity->team_id = $team->id;
            $activity->created_by_id = $userId;
            $activity->type = $type;
            $activity->title = $core['title'];
            $activity->description = $core['description'] ?? null;
            $activity->priority = $core['priority'] ?? 'medium';
            $activity->visibility = $core['visibility'] ?? 'private';
            $activity->is_template = $core['is_template'] ?? false;
            $activity->status = $core['status'] ?? ['value' => 'pending', 'label' => 'Pendiente'];
            $activity->due_date = !empty($core['due_date']) ? \Carbon\Carbon::parse($core['due_date']) : null;
            $activity->scheduled_date = !empty($core['scheduled_date']) ? \Carbon\Carbon::parse($core['scheduled_date']) : null;
            $activity->original_due_date = $activity->due_date;
            $activity->progress_percentage = 0;
            $activity->kanban_order = 0;

            // Asignar Urgencia al metadata base
            $activity->urgency = $core['urgency'] ?? 'medium';

            // Inyectar Specs si el subtipo soporta el contrato
            if ($activity instanceof ExportableActivityInterface) {
                $activity->importSpecs($specs);
            } else {
                // Fallback genérico para tipos personalizados sin contrato estricto
                $meta = $activity->metadata ?? [];
                
                // Filtro de seguridad (Mass Assignment) usando TemplateLoader
                $loader = app(\App\Services\TemplateLoader::class);
                $template = $loader->getTemplate($activity->type);
                if ($template && isset($template['properties'])) {
                    $allowedKeys = array_keys($template['properties']);
                    // Siempre permitimos capítulos si es un documento (u otras propiedades reservadas)
                    if ($activity->type === 'document') {
                        $allowedKeys[] = 'chapters';
                    }
                    $specs = array_intersect_key($specs, array_flip($allowedKeys));
                }
                
                $activity->metadata = array_merge($meta, $specs);
            }
            if (empty($activity->uuid)) {
                $activity->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
            $activity->save();

            // ─── 3. Sincronización de Especialidades (Skills) ─────────────────
            if (!empty($core['skills']) && is_array($core['skills'])) {
                $skillNames = array_column($core['skills'], 'name');
                $skillIds = Skill::forTeamOrGlobal($team->id)
                    ->whereIn('name', $skillNames)
                    ->pluck('id');
                $activity->skills()->sync($skillIds);
            }

            // ─── 4. Sincronización de Etiquetas (Tags) ────────────────────────
            if (!empty($core['tags']) && is_array($core['tags'])) {
                foreach ($core['tags'] as $tagData) {
                    if (!empty($tagData['tag'])) {
                        $activity->tags()->create([
                            'tag'       => $tagData['tag'],
                            'color_hex' => $tagData['color_hex'] ?? '#6366f1',
                        ]);
                    }
                }
            }

            // ─── 5. Sincronización Kanban y Matrix ────────────────────────────
            if (method_exists($activity, 'syncKanbanColumn')) {
                $activity->syncKanbanColumn();
            }

            // ─── 6. Registro de Historial de Auditoría ────────────────────────
            if ($userId) {
                $activity->histories()->create([
                    'user_id' => $userId,
                    'action'  => 'imported',
                    'notes'   => "Importado mediante esquema JSON ({$version})"
                ]);
            }

            return $activity;
        });
    }

    /**
     * Exporta una actividad a la estructura de array compatible con JSON v2.
     *
     * @param Activity $activity
     * @return array
     */
    public function exportToJson(Activity $activity): array
    {
        $subtype = $activity->asSubtype();

        $core = [
            'type'           => $subtype->type ?? 'task',
            'title'          => $subtype->title,
            'description'    => $subtype->description,
            'priority'       => $subtype->priority,
            'urgency'        => $subtype->urgency,
            'visibility'     => $subtype->visibility,
            'is_template'    => $subtype->is_template,
            'status'         => $subtype->status,
            'due_date'       => $subtype->due_date ? $subtype->due_date->toIso8601String() : null,
            'scheduled_date' => $subtype->scheduled_date ? $subtype->scheduled_date->toIso8601String() : null,
            'skills'         => $subtype->skills->map(fn($s) => ['name' => $s->name, 'category' => $s->category])->toArray(),
            'tags'           => $subtype->tags->map(fn($t) => ['tag' => $t->tag, 'color_hex' => $t->color_hex])->toArray(),
        ];

        $specs = [];
        if ($subtype instanceof ExportableActivityInterface) {
            $specs = $subtype->exportSpecs();
        } else {
            // Cosecha genérica de metadatos excluyendo los propios del core
            $meta = $subtype->metadata ?? [];
            unset($meta['urgency']);
            $specs = $meta;
        }

        return [
            '$schema'     => 'https://sientia.com/schemas/activity-v2.json',
            'version'     => 'sientia_activity_v2',
            'exported_at' => now()->toIso8601String(),
            'core'        => $core,
            'specs'       => $specs,
        ];
    }
}
