<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\ResolvesSurveyParams;

class SurveyExportController extends Controller
{
    use ResolvesSurveyParams;

    /**
     * Duplicate a survey to a different context.
     */
    public function duplicate(Request $request, $team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('duplicate', $survey);

        // For global promotion, only admin
        $targetTeamId = $request->input('target_team_id'); // If null, it's global
        
        if ($targetTeamId === null && !Auth::user()->is_admin) {
            abort(403);
        }

        return DB::transaction(function () use ($survey, $targetTeamId) {
            $newSurvey = $survey->replicate([
                'team_id', 'created_by_id', 'uuid', 'published_at', 'closed_at'
            ]);
            
            $newSurvey->team_id = $targetTeamId;
            $newSurvey->created_by_id = Auth::id();
            $newSurvey->title = $survey->title . ' (' . __('Copia') . ')';
            $newSurvey->published_at = now();
            $newSurvey->save();

            foreach ($survey->questions as $question) {
                $newQuestion = $question->replicate(['survey_id']);
                $newSurvey->questions()->save($newQuestion);

                foreach ($question->options as $option) {
                    $newOption = $option->replicate(['question_id']);
                    $newQuestion->options()->save($newOption);
                }
            }

            $redirectRoute = $targetTeamId ? 'teams.surveys.edit' : 'global-surveys.edit';
            $params = $targetTeamId ? [$targetTeamId, $newSurvey] : [$newSurvey];

            return redirect()->route($redirectRoute, $params)
                ->with('success', __('Encuesta duplicada con éxito. Ahora puedes ajustarla.'));
        });
    }

    /**
     * Export a survey to JSON.
     */
    public function exportJson($team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('view', $survey);

        $survey->load('questions.options');

        $exportData = [
            'title' => $survey->title,
            'description' => $survey->description,
            'is_active' => $survey->is_active,
            'allow_multiple_votes' => $survey->allow_multiple_votes,
            'show_results_before_voting' => $survey->show_results_before_voting,
            'expires_at' => $survey->expires_at ? $survey->expires_at->toIso8601String() : null,
            'questions' => $survey->questions->map(function ($question) {
                return [
                    'title' => $question->title,
                    'description' => $question->description,
                    'instructions' => $question->instructions,
                    'type' => $question->type,
                    'is_required' => $question->is_required,
                    'order' => $question->order,
                    'options' => $question->options->map(function ($option) {
                        return [
                            'label' => $option->label,
                            'order' => $option->order,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        $filename = Str::slug($survey->title) . '-export-' . now()->format('Y-m-d') . '.json';

        return response()->json($exportData, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Import a survey from JSON.
     */
    public function importJson(Request $request, ?Team $team = null)
    {
        if (!$team && !Auth::user()->is_admin) {
            abort(403);
        }

        $request->validate([
            'json_file' => 'required|file|mimes:json,txt',
        ]);

        $content = file_get_contents($request->file('json_file')->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['questions'])) {
            return back()->with('error', __('El archivo JSON no es válido o tiene un formato incorrecto.'));
        }

        return DB::transaction(function () use ($team, $data) {
            $survey = Survey::create([
                'team_id' => $team ? $team->id : null,
                'created_by_id' => Auth::id(),
                'title' => ($data['title'] ?? __('Encuesta Importada')) . ' (' . __('Copia') . ')',
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'allow_multiple_votes' => $data['allow_multiple_votes'] ?? false,
                'show_results_before_voting' => $data['show_results_before_voting'] ?? false,
                'expires_at' => isset($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at']) : null,
                'published_at' => now(),
            ]);

            foreach ($data['questions'] as $qData) {
                $question = $survey->questions()->create([
                    'title' => $qData['title'],
                    'description' => $qData['description'] ?? null,
                    'instructions' => $qData['instructions'] ?? null,
                    'type' => $qData['type'],
                    'order' => $qData['order'] ?? 0,
                    'is_required' => $qData['is_required'] ?? true,
                ]);

                if (isset($qData['options']) && is_array($qData['options'])) {
                    foreach ($qData['options'] as $oData) {
                        $question->options()->create([
                            'label' => $oData['label'],
                            'order' => $oData['order'] ?? 0,
                        ]);
                    }
                }
            }

            $redirectRoute = $team ? 'teams.surveys.edit' : 'global-surveys.edit';
            $params = $team ? [$team, $survey] : [$survey];

            return redirect()->route($redirectRoute, $params)
                ->with('success', __('Encuesta importada con éxito. Ahora puedes revisarla.'));
        });
    }
}
