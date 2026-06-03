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
    public function index(?Team $team = null)
    {
        $query = $team 
            ? $team->surveys()
            : Survey::whereNull('team_id');

        $surveys = $query
            ->with(['creator', 'questions'])
            ->withCount(['votes as unique_voters_count' => function($q) {
                $q->select(DB::raw('count(distinct user_id)'));
            }])
            ->orderByDesc('published_at')
            ->paginate(12);

        $members = collect();
        $allTeams = [];

        if (!$team) {
            // Inicializar y obtener los miembros y equipos del mapa de citas
            $rawMembers = \App\Models\User::whereNotNull('location_lat')
                ->whereNotNull('location_lng')
                ->get()
                ->filter(fn($u) => $u->hasAppointmentsEnabled());

            foreach ($rawMembers as $u) {
                $userTeams = $u->teams()
                    ->whereJsonContains('settings->has_appointments', true)
                    ->wherePivot('allow_appointments', true)
                    ->get();

                foreach ($userTeams as $t) {
                    // Asegurar que tengan appointment_settings con is_public = true por defecto para este equipo
                    $settingsExist = $u->appointmentSettings()->where('team_id', $t->id)->exists();
                    if (!$settingsExist) {
                        $u->appointmentSettings()->create([
                            'team_id' => $t->id,
                            'public_slug' => \Illuminate\Support\Str::slug($u->name) . '-' . $u->id . '-' . $t->id,
                            'display_name' => $u->name,
                            'is_public' => true,
                            'default_slot_duration' => 15,
                            'default_max_per_slot' => 1,
                            'auto_create_task' => true,
                            'email_confirmation' => true,
                        ]);
                    }

                    // Asegurar que tengan al menos 1 servicio activo para este equipo
                    $servicesExist = $u->appointmentServices()->where('team_id', $t->id)->active()->exists();
                    if (!$servicesExist) {
                        $service = $u->appointmentServices()->create([
                            'team_id' => $t->id,
                            'name' => 'Consulta General',
                            'description' => 'Consulta o asesoramiento general de información.',
                            'duration_minutes' => 15,
                            'is_active' => true,
                            'price' => null,
                            'price_visible' => false,
                            'modality' => ['presencial'],
                        ]);

                        for ($day = 1; $day <= 5; $day++) {
                            \App\Models\AppointmentSchedule::create([
                                'user_id' => $u->id,
                                'service_id' => $service->id,
                                'day_of_week' => $day,
                                'start_time' => '09:00',
                                'end_time' => '14:00',
                                'slot_duration_minutes' => 15,
                                'max_per_slot' => 1,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }

            // Consultar la lista de configuraciones públicas
            $settings = \App\Models\AppointmentSettings::where('is_public', true)
                ->whereHas('user', fn($q) => $q->whereNotNull('location_lat')->whereNotNull('location_lng'))
                ->with(['user', 'team'])
                ->get()
                ->filter(fn($s) => $s->user->hasAppointmentsEnabled());

            $members = $settings->map(fn($s) => [
                'slug'         => $s->public_slug,
                'display_name' => $s->display_name ?: $s->user->name,
                'lat'          => $s->user->location_lat,
                'lng'          => $s->user->location_lng,
                'services'     => $s->user->appointmentServices()->where('team_id', $s->team_id)->active()->count(),
                'area'         => $s->user->working_area_name ?: 'Área Territorial',
                'teams'        => $s->team ? [$s->team->name] : [],
            ])
            ->filter(fn($item) => $item['services'] > 0)
            ->values();

            $allTeams = $members->pluck('teams')->flatten()->unique()->sort()->values()->toArray();
        }

        return view('surveys.index', compact('team', 'surveys', 'members', 'allTeams'));
    }

    /**
     * Show the form for creating a new survey.
     */
    public function create(?Team $team = null)
    {
        if (!$team && !Auth::user()->is_admin) {
            abort(403);
        }
        return view('surveys.create', compact('team'));
    }

    /**
     * Store a newly created survey.
     */
    public function store(Request $request, ?Team $team = null)
    {
        if (!$team && !Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'questions' => 'required|array|min:1',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.description' => 'nullable|string',
            'questions.*.instructions' => 'nullable|string',
            'questions.*.type' => 'required|in:single_choice,multiple_choice,rating,text',
            'questions.*.options' => 'required_if:questions.*.type,single_choice,multiple_choice|array',
            'questions.*.options.*.id' => 'nullable', 
            'questions.*.options.*.label' => 'nullable|string|max:255',
            'questions.*.is_required' => 'boolean',
        ]);

        return DB::transaction(function () use ($team, $validated, $request) {
            $data = [
                'team_id' => $team ? $team->id : null,
                'created_by_id' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'is_public' => $request->boolean('is_public'),
                'allow_multiple_votes' => $request->boolean('allow_multiple_votes'),
                'show_results_before_voting' => $request->boolean('show_results_before_voting'),
                'expires_at' => $validated['expires_at'] ?? null,
                'published_at' => now(),
            ];

            $survey = Survey::create($data);

            foreach ($validated['questions'] as $index => $qData) {
                $question = $survey->questions()->create([
                    'title' => $qData['title'],
                    'description' => $qData['description'] ?? null,
                    'instructions' => $qData['instructions'] ?? null,
                    'type' => $qData['type'],
                    'order' => $index,
                    'is_required' => $qData['is_required'] ?? true,
                ]);

                if (in_array($qData['type'], ['single_choice', 'multiple_choice']) && !empty($qData['options'])) {
                    foreach ($qData['options'] as $oIndex => $oData) {
                        $label = is_array($oData) ? ($oData['label'] ?? '') : $oData;
                        if ($label && trim($label) !== '') {
                            $question->options()->create([
                                'label' => trim($label),
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

            $redirectRoute = $team ? 'teams.surveys.show' : 'global-surveys.show';
            $params = $team ? [$team, $survey] : [$survey];

            return redirect()->route($redirectRoute, $params)
                ->with('success', __('Encuesta creada con éxito.'));
        });
    }

    /**
     * Display a survey.
     */
    public function show($team, $survey = null)
    {
        $this->resolveParams($team, $survey);

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

        $totalVotes = $survey->votes()->select('user_id')->distinct()->count('user_id');
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
    public function edit($team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('update', $survey);
        
        $survey->load('questions.options');
        
        $questions = $survey->questions->map(function($q) {
            return [
                'id' => $q->id . '_' . time(), 
                'db_id' => $q->id,
                'title' => $q->title,
                'description' => $q->description ?? '',
                'instructions' => $q->instructions ?? '',
                'type' => $q->type,
                'is_required' => (bool)$q->is_required,
                'options' => $q->options->map(function($o) {
                    return ['db_id' => $o->id, 'label' => $o->label];
                })->toArray() ?: [['db_id' => null, 'label' => ''], ['db_id' => null, 'label' => '']]
            ];
        });

        return view('surveys.edit', compact('team', 'survey', 'questions'));
    }

    /**
     * Update a survey.
     */
    public function update(Request $request, $team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('update', $survey);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'allow_multiple_votes' => 'boolean',
            'show_results_before_voting' => 'boolean',
            'expires_at' => 'nullable|date',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|exists:survey_questions,id',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.description' => 'nullable|string',
            'questions.*.instructions' => 'nullable|string',
            'questions.*.type' => 'required|in:single_choice,multiple_choice,rating,text',
            'questions.*.options' => 'required_if:questions.*.type,single_choice,multiple_choice|array',
            'questions.*.options.*.id' => 'nullable|exists:survey_options,id',
            'questions.*.options.*.label' => 'nullable|string|max:255',
            'questions.*.is_required' => 'boolean',
        ]);

        return DB::transaction(function () use ($team, $survey, $validated, $request) {
            $survey->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'is_public' => $request->boolean('is_public'),
                'allow_multiple_votes' => $request->boolean('allow_multiple_votes'),
                'show_results_before_voting' => $request->boolean('show_results_before_voting'),
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            $keepQuestionIds = [];

            foreach ($validated['questions'] as $index => $qData) {
                $questionData = [
                    'title' => $qData['title'],
                    'description' => $qData['description'] ?? null,
                    'instructions' => $qData['instructions'] ?? null,
                    'type' => $qData['type'],
                    'order' => $index,
                    'is_required' => $qData['is_required'] ?? true,
                ];

                if (isset($qData['id'])) {
                    $question = $survey->questions()->find($qData['id']);
                    $question->update($questionData);
                } else {
                    $question = $survey->questions()->create($questionData);
                }
                
                $keepQuestionIds[] = $question->id;

                // Sync Options for Choice Questions
                if (in_array($qData['type'], ['single_choice', 'multiple_choice'])) {
                    $requestedOptions = $qData['options'] ?? [];
                    $keepOptionIds = [];

                    foreach ($requestedOptions as $oIndex => $oData) {
                        $label = is_array($oData) ? ($oData['label'] ?? '') : $oData;
                        $optionId = is_array($oData) ? ($oData['id'] ?? null) : null;

                        if (trim($label) === '') continue;

                        if ($optionId) {
                            $option = $question->options()->find($optionId);
                            if ($option) {
                                $option->update([
                                    'label' => trim($label),
                                    'order' => $oIndex
                                ]);
                                $keepOptionIds[] = $option->id;
                            } else {
                                // ID provided but not found for this question? Create it.
                                $newOption = $question->options()->create([
                                    'label' => trim($label),
                                    'order' => $oIndex
                                ]);
                                $keepOptionIds[] = $newOption->id;
                            }
                        } else {
                            $newOption = $question->options()->create([
                                'label' => trim($label),
                                'order' => $oIndex
                            ]);
                            $keepOptionIds[] = $newOption->id;
                        }
                    }

                    // Delete removed options
                    $question->options()->whereNotIn('id', $keepOptionIds)->delete();
                } elseif ($qData['type'] === 'rating') {
                    // Standardize rating options (1-5)
                    if ($question->options()->count() !== 5) {
                        $question->options()->delete();
                        for ($i = 1; $i <= 5; $i++) {
                            $question->options()->create([
                                'label' => (string)$i,
                                'order' => $i,
                            ]);
                        }
                    }
                } else {
                    // For 'text' type, clean up any existing options
                    $question->options()->delete();
                }
            }

            // Remove questions that are no longer in the list
            $survey->questions()->whereNotIn('id', $keepQuestionIds)->delete();

            $redirectRoute = $team ? 'teams.surveys.show' : 'global-surveys.show';
            $params = $team ? [$team, $survey] : [$survey];

            return redirect()->route($redirectRoute, $params)
                ->with('success', __('Encuesta actualizada con éxito.'));
        });
    }

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

    /**
     * Remove a survey.
     */
    public function destroy($team, $survey = null)
    {
        $this->resolveParams($team, $survey);
        $this->authorize('delete', $survey);
        $survey->delete();
        
        $redirectRoute = $team ? 'teams.surveys.index' : 'global-surveys.index';
        $params = $team ? [$team] : [];

        return redirect()->route($redirectRoute, $params)->with('success', __('Encuesta eliminada.'));
    }

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

    /**
     * Resolve and standardize $team and $survey parameters for both team-scoped and global routes.
     */
    private function resolveParams(&$team, &$survey)
    {
        if ($survey === null) {
            $survey = $team;
            $team = null;
        }

        if (!$survey instanceof Survey) {
            $survey = Survey::findOrFail($survey);
        }

        if ($team && !$team instanceof Team) {
            $team = Team::findOrFail($team);
        }
    }
}
