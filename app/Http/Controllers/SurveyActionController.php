<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\ResolvesSurveyParams;

class SurveyActionController extends Controller
{
    use ResolvesSurveyParams;

    /**
     * Vote on a survey.
     */
    public function vote(Request $request, $team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('view', $survey);

        if ($survey->is_closed) {
            return back()->with('error', __('Esta encuesta está cerrada.'));
        }

        $user = Auth::user();
        
        // Validation for each question
        $rules = [];
        foreach ($survey->questions as $question) {
            if ($question->type === 'single_choice') {
                $rules["answers.{$question->id}"] = $question->is_required ? 'required|exists:survey_options,id' : 'nullable|exists:survey_options,id';
            } elseif ($question->type === 'multiple_choice') {
                $rules["answers.{$question->id}"] = $question->is_required ? 'required|array|min:1' : 'nullable|array';
                $rules["answers.{$question->id}.*"] = 'exists:survey_options,id';
            } elseif ($question->type === 'rating') {
                $rules["answers.{$question->id}"] = $question->is_required ? 'required|integer|min:1|max:5' : 'nullable|integer|min:1|max:5';
            } elseif ($question->type === 'text') {
                $rules["answers.{$question->id}"] = $question->is_required ? 'required|string|max:2000' : 'nullable|string|max:2000';
            }
        }

        $validated = $request->validate($rules);
        $answers = $validated['answers'] ?? [];

        return DB::transaction(function () use ($survey, $user, $answers) {
            // Remove previous votes
            $survey->votes()->where('user_id', $user->id)->delete();

            foreach ($survey->questions as $question) {
                if (!isset($answers[$question->id])) continue;

                $val = $answers[$question->id];

                if ($question->type === 'single_choice') {
                    $question->votes()->create([
                        'user_id' => $user->id,
                        'option_id' => $val,
                        'voted_at' => now(),
                    ]);
                } elseif ($question->type === 'multiple_choice') {
                    foreach ($val as $optionId) {
                        $question->votes()->create([
                            'user_id' => $user->id,
                            'option_id' => $optionId,
                            'voted_at' => now(),
                        ]);
                    }
                } elseif ($question->type === 'rating') {
                    $option = $question->options()->where('label', (string)$val)->first();
                    if ($option) {
                        $question->votes()->create([
                            'user_id' => $user->id,
                            'option_id' => $option->id,
                            'voted_at' => now(),
                        ]);
                    }
                } elseif ($question->type === 'text') {
                    $question->votes()->create([
                        'user_id' => $user->id,
                        'text_value' => strip_tags($val),
                        'voted_at' => now(),
                    ]);
                }
            }

            return back()->with('success', __('¡Votos registrados correctamente!'));
        });
    }

    /**
     * Close a survey.
     */
    public function close($team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('update', $survey);
        $survey->update(['closed_at' => now()]);
        return back()->with('success', __('Encuesta cerrada.'));
    }

    /**
     * Reactivate a survey.
     */
    public function reactivate($team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('update', $survey);
        $survey->update(['closed_at' => null]);
        return back()->with('success', __('Encuesta reactivada.'));
    }
}
