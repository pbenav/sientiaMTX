<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Survey;
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
            ->with(['creator', 'options'])
            ->orderByDesc('published_at')
            ->paginate(15);

        $activeSurveys = $team->surveys()->active()->count();

        return view('surveys.index', compact('team', 'surveys', 'activeSurveys'));
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
            'type' => 'required|in:single_choice,multiple_choice,rating,text',
            'options' => 'required_if:type,single_choice,multiple_choice|array',
            'options.*' => 'string|max:255',
            'option_colors' => 'array',
            'option_colors.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        return DB::transaction(function () use ($request, $team, $validated) {
            $survey = $team->surveys()->create([
                'created_by_id' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'is_active' => $validated['is_active'] ?? true,
                'allow_multiple_votes' => $validated['allow_multiple_votes'] ?? false,
                'show_results_before_voting' => $validated['show_results_before_voting'] ?? false,
                'expires_at' => $validated['expires_at'] ?? null,
                'published_at' => now(),
            ]);

            // Create options for single_choice or multiple_choice
            if (in_array($validated['type'], ['single_choice', 'multiple_choice']) && !empty($validated['options'])) {
                foreach ($validated['options'] as $index => $optionLabel) {
                    if (trim($optionLabel) !== '') {
                        $color = $validated['option_colors'][$index] ?? null;
                        $survey->options()->create([
                            'label' => trim($optionLabel),
                            'order' => $index,
                            'color' => $color,
                        ]);
                    }
                }
            }

            return redirect()->route('teams.surveys.show', [$team, $survey])
                ->with('success', __('surveys.survey_created'));
        });
    }

    /**
     * Display a survey.
     */
    public function show(Team $team, Survey $survey)
    {
        $survey->load(['creator', 'options']);
        
        $user = Auth::user();
        $hasVoted = $survey->hasVoted($user);
        $userVotes = $hasVoted ? $survey->votes()->where('user_id', $user->id)->pluck('option_id')->flatten()->toArray() : [];
        
        // Get results if allowed
        $showResults = $survey->show_results_before_voting || $survey->is_closed || $hasVoted;
        $results = [];
        
        if ($showResults) {
            foreach ($survey->options as $option) {
                $voteCount = $survey->getOptionVoteCount($option->id);
                $percentage = $survey->getOptionPercentage($option->id);
                $isUserSelected = in_array($option->id, $userVotes);
                
                $results[] = [
                    'option' => $option,
                    'vote_count' => $voteCount,
                    'percentage' => $percentage,
                    'is_user_selected' => $isUserSelected,
                ];
            }
        }

        return view('surveys.show', compact('team', 'survey', 'hasVoted', 'userVotes', 'showResults', 'results'));
    }

    /**
     * Show the form for editing a survey.
     */
    public function edit(Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);
        
        $survey->load('options');
        
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
            'type' => 'required|in:single_choice,multiple_choice,rating,text',
            'options' => 'required_if:type,single_choice,multiple_choice|array',
            'options.*' => 'string|max:255',
            'option_colors' => 'array',
            'option_colors.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        return DB::transaction(function () use ($request, $survey, $validated) {
            $survey->update($validated);

            // Delete old options and create new ones
            if (in_array($validated['type'], ['single_choice', 'multiple_choice'])) {
                $survey->options()->delete();
                foreach ($validated['options'] as $index => $optionLabel) {
                    if (trim($optionLabel) !== '') {
                        $color = $validated['option_colors'][$index] ?? null;
                        $survey->options()->create([
                            'label' => trim($optionLabel),
                            'order' => $index,
                            'color' => $color,
                        ]);
                    }
                }
            }

            return redirect()->route('teams.surveys.show', [$survey->team_id, $survey])
                ->with('success', __('Survey updated successfully'));
        });
    }

    /**
     * Vote on a survey.
     */
    public function vote(Request $request, Team $team, Survey $survey)
    {
        $this->authorize('vote', $survey);

        $validated = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'exists:survey_options,id',
            'text_value' => 'nullable|string|max:1000',
        ]);

        // Check if survey is closed
        if ($survey->is_closed) {
            return back()->with('error', __('This survey is closed'));
        }

        // Check expiration
        if ($survey->is_expired) {
            return back()->with('error', __('This survey has expired'));
        }

        return DB::transaction(function () use ($request, $survey, $validated) {
            $user = Auth::user();

            // Delete previous votes if multiple votes not allowed
            if (!$survey->allow_multiple_votes) {
                SurveyVote::where('survey_id', $survey->id)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            // Handle different survey types
            if (in_array($survey->type, ['single_choice', 'multiple_choice'])) {
                foreach ($validated['option_ids'] as $optionId) {
                    SurveyVote::create([
                        'survey_id' => $survey->id,
                        'option_id' => $optionId,
                        'user_id' => $user->id,
                        'voted_at' => now(),
                    ]);
                }
            }

            if ($survey->type === 'text') {
                if (!empty($validated['text_value'])) {
                    SurveyVote::create([
                        'survey_id' => $survey->id,
                        'option_id' => null,
                        'user_id' => $user->id,
                        'text_value' => $validated['text_value'],
                        'voted_at' => now(),
                    ]);
                }
            }

            if ($survey->type === 'rating') {
                foreach ($validated['option_ids'] as $optionId) {
                    $option = SurveyOption::find($optionId);
                    if ($option && is_numeric($option->label)) {
                        SurveyVote::create([
                            'survey_id' => $survey->id,
                            'option_id' => $optionId,
                            'user_id' => $user->id,
                            'voted_at' => now(),
                        ]);
                    }
                }
            }

            return back()->with('success', __('surveys.vote_submitted'));
        });
    }

    /**
     * Close a survey.
     */
    public function close(Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);

        $survey->update(['closed_at' => now()]);

        return back()->with('success', __('surveys.survey_closed'));
    }

    /**
     * Reactivate a survey.
     */
    public function reactivate(Team $team, Survey $survey)
    {
        $this->authorize('update', $survey);

        $survey->update([
            'is_active' => true,
            'closed_at' => null,
        ]);

        return back()->with('success', __('surveys.survey_reactivated'));
    }

    /**
     * Delete a survey.
     */
    public function destroy(Team $team, Survey $survey)
    {
        $this->authorize('delete', $survey);

        $survey->delete();

        return redirect()->route('teams.surveys.index', $team)
            ->with('success', __('surveys.survey_deleted'));
    }

    /**
     * Get survey results via AJAX.
     */
    public function results(Team $team, Survey $survey)
    {
        $this->authorize('view', $survey);

        $survey->load('options');

        $results = [];
        foreach ($survey->options as $option) {
            $voteCount = $survey->getOptionVoteCount($option->id);
            $percentage = $survey->getOptionPercentage($option->id);
            
            $results[] = [
                'option' => $option,
                'vote_count' => $voteCount,
                'percentage' => $percentage,
            ];
        }

        return response()->json([
            'total_votes' => $survey->vote_count,
            'results' => $results,
        ]);
    }
}
