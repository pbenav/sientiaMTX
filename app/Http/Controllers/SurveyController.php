<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyOption;
use App\Models\SurveyVote;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    /**
     * Display a listing of surveys for a team.
     */
    public function index(Team $team)
    {
        $surveys = $team->surveys()
            ->with(['creator', 'questions'])
            ->withCount('votes')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('surveys.index', compact('team', 'surveys'));
    }

    /**
     * Show the form for creating a new survey.
     */
    public function create(Team $team)
    {
        return view('surveys.create', compact('team'));
    }

    /**
     * Store a newly created survey.
     */
    public function store(Request $request, Team $team)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'questions' => 'required|array|min:1',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.type' => 'required|in:single_choice,multiple_choice,rating,text',
            'questions.*.options' => 'required_if:questions.*.type,single_choice,multiple_choice|array',
            'questions.*.options.*' => 'nullable|string|max:255',
            'questions.*.is_required' => 'boolean',
        ]);

        return DB::transaction(function () use ($team, $validated) {
            $survey = $team->surveys()->create([
                'created_by_id' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'allow_multiple_votes' => $validated['allow_multiple_votes'] ?? false,
                'show_results_before_voting' => $validated['show_results_before_voting'] ?? false,
                'expires_at' => $validated['expires_at'] ?? null,
                'published_at' => now(),
            ]);

            foreach ($validated['questions'] as $index => $qData) {
                $question = $survey->questions()->create([
                    'title' => $qData['title'],
                    'type' => $qData['type'],
                    'order' => $index,
                    'is_required' => $qData['is_required'] ?? true,
                ]);

                if (in_array($qData['type'], ['single_choice', 'multiple_choice']) && !empty($qData['options'])) {
                    foreach ($qData['options'] as $oIndex => $oLabel) {
                        if ($oLabel && trim($oLabel) !== '') {
                            $question->options()->create([
                                'label' => trim($oLabel),
                                'order' => $oIndex,
                            ]);
                        }
                    }
                } elseif ($qData['type'] === 'rating') {
                    for ($i = 1; $i <= 5; $i++) {
                        $question->options()->create([
                            'label' => (string)$i,
                            'order' => $i,
                        ]);
                    }
                }
            }

            return redirect()->route('teams.surveys.show', [$team, $survey])
                ->with('success', __('Encuesta creada con éxito.'));
        });
    }

    /**
     * Display a survey.
     */
    public function show(Team $team, Survey $survey)
    {
        $survey->load(['creator', 'questions.options' => function($q) {
            $q->withCount('votes');
        }]);
        
        $user = Auth::user();
        $hasVoted = $survey->hasVoted($user);
        
        // Get user votes grouped by question
        $userVotes = $survey->votes()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('question_id');

        $totalVotes = $survey->votes()->distinct('user_id')->count();
        $showResults = $survey->show_results_before_voting || $survey->is_closed || $hasVoted;

        return view('surveys.show', compact(
            'team', 
            'survey', 
            'hasVoted', 
            'userVotes',
            'showResults', 
            'totalVotes'
        ));
    }

    /**
     * Show the form for editing a survey.
     */
    public function edit(Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);
        $survey->load('questions.options');
        return view('surveys.edit', compact('team', 'survey'));
    }

    /**
     * Update a survey.
     */
    public function update(Request $request, Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $survey->update($validated);

        return redirect()->route('teams.surveys.show', [$team, $survey])
            ->with('success', __('Encuesta actualizada.'));
    }

    /**
     * Vote on a survey.
     */
    public function vote(Request $request, Team $team, Survey $survey)
    {
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
                        'text_value' => $val,
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
    public function close(Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);
        $survey->update(['closed_at' => now()]);
        return back()->with('success', __('Encuesta cerrada.'));
    }

    /**
     * Reactivate a survey.
     */
    public function reactivate(Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);
        $survey->update(['closed_at' => null]);
        return back()->with('success', __('Encuesta reactivada.'));
    }

    /**
     * Remove a survey.
     */
    public function destroy(Team $team, Survey $survey)
    {
        $this->authorize('delete', $survey);
        $survey->delete();
        return redirect()->route('teams.surveys.index', $team)->with('success', __('Encuesta eliminada.'));
    }
}
