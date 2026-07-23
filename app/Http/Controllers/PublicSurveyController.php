<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyVote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controlador público para la visualización y votación en encuestas accesibles sin autenticación.
 *
 * Gestiona la consulta de encuestas públicas activas por UUID, la verificación de votos previos,
 * la previsualización de resultados y el almacenamiento de respuestas. Soporta votación anónima
 * mediante session ID y votación con usuario autenticado.
 *
 * Rutas asociadas:
 *   - GET /surveys/{uuid}
 *   - POST /surveys/{uuid}
 */
class PublicSurveyController extends Controller
{
    /**
     * Muestra la interfaz de una encuesta pública para su visualización o votación.
     *
     * Determina si el usuario ya votó, carga sus respuestas previas para prellenar,
     * calcula el total de votos y determina si se deben mostrar los resultados.
     *
     * @param string $uuid Identificador único de la encuesta
     * @return \Illuminate\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function show(string $uuid)
    {
        $survey = Survey::where('uuid', $uuid)
            ->where('is_public', true)
            ->where('is_active', true)
            ->with(['questions.options', 'creator'])
            ->firstOrFail();

        if ($survey->is_expired || $survey->closed_at !== null) {
            // It might be closed. We might still want to show the results if show_results_before_voting is true.
        }

        $session_id = session()->getId();
        $user_id = auth()->id();

        // Check if voted
        $hasVoted = false;
        if ($user_id) {
            $hasVoted = $survey->votes()->where('user_id', $user_id)->exists();
        } else {
            $hasVoted = $survey->votes()->where('session_id', $session_id)->exists();
        }

        // Get user votes to pre-fill if already voted
        $userVotes = collect();
        if ($hasVoted) {
            if ($user_id) {
                $userVotes = $survey->votes()->where('user_id', $user_id)->get()->groupBy('question_id');
            } else {
                $userVotes = $survey->votes()->where('session_id', $session_id)->get()->groupBy('question_id');
            }
        }

        $showResults = $survey->show_results_before_voting || ($hasVoted && !$survey->allow_multiple_votes) || $survey->is_closed;
        $totalVotes = $survey->votes()->distinct('user_id')->count('user_id') + $survey->votes()->whereNull('user_id')->distinct('session_id')->count('session_id');

        return view('surveys.public-show', compact('survey', 'hasVoted', 'showResults', 'userVotes', 'totalVotes'));
    }

    /**
     * Almacena las respuestas de una encuesta pública.
     *
     * Valida que la encuesta esté abierta y que el votante no haya votado previamente
     * (si no se permite votación múltiple). Elimina votos previos del mismo usuario/sesión
     * y crea los votos correspondientes según el tipo de pregunta (elección simple, múltiple,
     * texto o calificación).
     *
     * @param Request $request
     * @param string $uuid Identificador único de la encuesta
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $uuid)
    {
        $survey = Survey::where('uuid', $uuid)
            ->where('is_public', true)
            ->where('is_active', true)
            ->firstOrFail();

        if ($survey->is_closed) {
            return back()->with('error', 'Esta encuesta ya está cerrada.');
        }

        $session_id = session()->getId();
        $user_id = auth()->id();

        if (!$survey->allow_multiple_votes) {
            if ($user_id) {
                if ($survey->hasVoted(auth()->user())) {
                    return back()->with('error', 'Ya has votado en esta encuesta.');
                }
            } else {
                if ($survey->votes()->where('session_id', $session_id)->exists()) {
                    return back()->with('error', 'Ya has votado en esta encuesta.');
                }
            }
        }

        // Validate
        $rules = [];
        foreach ($survey->questions as $question) {
            if ($question->is_required) {
                $rules['answers.' . $question->id] = 'required';
            }
        }
        $request->validate($rules);

        // Remove old votes if they are re-voting
        if ($user_id) {
            $survey->votes()->where('user_id', $user_id)->delete();
        } else {
            $survey->votes()->where('session_id', $session_id)->delete();
        }

        // Store votes
        foreach ($request->input('answers', []) as $questionId => $answer) {
            $question = $survey->questions()->find($questionId);
            if (!$question) continue;

            if ($question->type === 'multiple_choice' && is_array($answer)) {
                foreach ($answer as $optionId) {
                    SurveyVote::create([
                        'question_id' => $questionId,
                        'option_id' => $optionId,
                        'user_id' => $user_id,
                        'session_id' => $session_id,
                        'voted_at' => now(),
                    ]);
                }
            } elseif ($question->type === 'text') {
                SurveyVote::create([
                    'question_id' => $questionId,
                    'text_value' => strip_tags($answer),
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'voted_at' => now(),
                ]);
            } elseif ($question->type === 'rating') {
                $option = $question->options()->where('label', (string)$answer)->first();
                if ($option) {
                    SurveyVote::create([
                        'question_id' => $questionId,
                        'option_id' => $option->id,
                        'user_id' => $user_id,
                        'session_id' => $session_id,
                        'voted_at' => now(),
                    ]);
                }
            } else { // single_choice
                SurveyVote::create([
                    'question_id' => $questionId,
                    'option_id' => $answer,
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'voted_at' => now(),
                ]);
            }
        }

        return back()->with('success', '¡Tus respuestas han sido enviadas correctamente!');
    }
}
