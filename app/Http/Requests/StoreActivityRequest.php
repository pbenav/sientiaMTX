<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

class StoreActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('team');
        $activity = $this->route('activity');

        if ($activity) {
            return auth()->user()->can('view', $team) && auth()->user()->can('update', $activity);
        }

        return auth()->user()->can('view', $team) && auth()->user()->can('create', [Activity::class, $team]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $team = $this->route('team');
        $activityTypes = array_keys(Activity::SUBTYPES);

        $rules = [
            'type' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                Rule::in($activityTypes)
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,private,semi-private',
            'priority' => 'required|in:low,medium,high,critical',
            'urgency' => 'nullable|in:low,medium,high,critical',
            'auto_priority' => 'nullable|boolean',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'due_date' => 'nullable|date',
            'scheduled_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'integer|exists:users,id',
            'assigned_groups' => 'nullable|array',
            'assigned_groups.*' => 'integer|exists:groups,id',
            'parent_id' => [
                'nullable',
                Rule::exists('activities', 'id')->where('team_id', $team->id),
            ],
            'expediente_id' => [
                'nullable',
                Rule::exists('expedientes', 'id')->where('team_id', $team->id),
            ],
            'is_template' => 'nullable|boolean',
            'kanban_column_id' => [
                'nullable',
                Rule::exists('kanban_columns', 'id')->where('team_id', $team->id),
            ],
            'kanban_order' => 'nullable|integer',
            'matrix_order' => 'nullable|integer',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'metadata' => 'nullable|array',
            'metadata.*' => 'nullable',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . (UploadedFile::getMaxFilesize() / 1024),
        ];

        // -------------------------------------------------------------
        // INYECCIÓN DINÁMICA DE REGLAS BASADA EN PLANTILLAS JSON
        // -------------------------------------------------------------
        $type = $this->input('type');
        if (!$type && $this->route('activity')) {
            $type = $this->route('activity')->type;
        }

        if ($type && array_key_exists($type, Activity::SUBTYPES)) {
            $loader = app(\App\Services\TemplateLoader::class);
            $template = $loader->getTemplate($type);

            if ($template && isset($template['properties'])) {
                $requiredFields = $template['required'] ?? [];

                foreach ($template['properties'] as $key => $propRules) {
                    $fieldRules = [];

                    // 1. Required o Nullable
                    if (in_array($key, $requiredFields, true)) {
                        // Solo exigimos required absoluto si es un POST y no hay default
                        // (Si hay default, se autocompletará luego en el service, o podemos dejarlo required)
                        $fieldRules[] = isset($propRules['default']) ? 'nullable' : 'required';
                    } else {
                        $fieldRules[] = 'nullable';
                    }

                    // 2. Tipos base
                    $propType = $propRules['type'] ?? 'string';
                    switch ($propType) {
                        case 'string':
                            $fieldRules[] = 'string';
                            break;
                        case 'integer':
                            $fieldRules[] = 'integer';
                            break;
                        case 'number':
                            $fieldRules[] = 'numeric';
                            break;
                        case 'boolean':
                            $fieldRules[] = 'boolean';
                            break;
                        case 'array':
                            $fieldRules[] = 'array';
                            break;
                    }

                    // 3. Enum
                    if (isset($propRules['enum']) && is_array($propRules['enum'])) {
                        $fieldRules[] = Rule::in($propRules['enum']);
                    }

                    // 4. Reglas extra de validación del sistema antiguo (ej: URL o exists)
                    // Para mantener compatibilidad estricta con el diseño anterior que validaba en la BD
                    if ($key === 'url') {
                        $fieldRules[] = 'url';
                        $fieldRules[] = 'max:1000';
                    }
                    if ($key === 'service_id') {
                        $fieldRules[] = Rule::exists('services', 'id')->where('team_id', $team->id);
                    }
                    if ($key === 'skill_id') {
                        $fieldRules[] = 'exists:skills,id';
                    }

                    $rules[$key] = $fieldRules;
                }
            }
        }

        return $rules;
    }
}
