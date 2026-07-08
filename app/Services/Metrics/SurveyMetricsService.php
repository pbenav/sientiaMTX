<?php

namespace App\Services\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SurveyMetricsService
{
    public function getResponseRate(int $surveyId): array
    {
        $survey = DB::table('surveys')->where('id', $surveyId)->first();
        if (!$survey) return ['target' => 0, 'responded' => 0, 'rate' => 0];

        $targetCount = 0;
        if ($survey->target_user_ids) {
            $targetCount = count(json_decode($survey->target_user_ids, true));
        } else {
            $targetCount = DB::table('users')->count();
        }

        $responded = DB::table('survey_votes')
            ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
            ->where('survey_questions.survey_id', $surveyId)
            ->distinct('survey_votes.user_id')
            ->count('survey_votes.user_id');

        return [
            'target' => $targetCount,
            'responded' => $responded,
            'rate' => $targetCount > 0 ? round(($responded / $targetCount) * 100, 1) : 0,
        ];
    }

    public function getScoresByQuestion(int $surveyId): array
    {
        $rows = DB::table('survey_votes')
            ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
            ->where('survey_questions.survey_id', $surveyId)
            ->selectRaw('survey_questions.title as question, survey_votes.text_value as text_value, survey_questions.id as question_id, survey_questions.type as question_type')
            ->get();

        $byQuestion = $rows->groupBy('question_id')->map(function ($votes) {
            $text = $votes->first()->question;
            $numericValues = $votes->map(function ($v) {
                return $this->parseTextToScore($v->text_value);
            })->filter()->values();

            $avg = $numericValues->isNotEmpty() ? $numericValues->avg() : 0;
            $min = $numericValues->isNotEmpty() ? $numericValues->min() : 0;
            $max = $numericValues->isNotEmpty() ? $numericValues->max() : 0;

            return [
                'question' => $text,
                'question_id' => $votes->first()->question_id,
                'question_type' => $votes->first()->question_type,
                'avg_score' => round($avg, 2),
                'min_score' => round($min, 2),
                'max_score' => round($max, 2),
                'response_count' => $votes->count(),
                'distribution' => $votes->groupBy('text_value')->map->count()->toArray(),
            ];
        })->values()->toArray();

        return $byQuestion;
    }

    public function getNPS(int $surveyId): array
    {
        $numericVotes = DB::table('survey_votes')
            ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
            ->where('survey_questions.survey_id', $surveyId)
            ->where('survey_questions.type', 'nps')
            ->select('survey_votes.text_value')
            ->pluck('text_value')
            ->map(function ($v) {
                return $this->parseTextToScore($v);
            })->filter(fn($v) => is_numeric($v))->map(fn($v) => (float) $v);

        if ($numericVotes->isEmpty()) return ['nps' => 0, 'promoters' => 0, 'passives' => 0, 'detractors' => 0, 'total' => 0];

        $promoters = $numericVotes->filter(fn($v) => $v >= 9)->count();
        $passives = $numericVotes->filter(fn($v) => $v >= 7 && $v <= 8)->count();
        $detractors = $numericVotes->filter(fn($v) => $v <= 6)->count();
        $total = $numericVotes->count();

        $nps = $total > 0 ? round(($promoters - $detractors) / $total * 100, 1) : 0;

        return [
            'nps' => $nps,
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'total' => $total,
            'promoter_pct' => $total > 0 ? round(($promoters / $total) * 100, 1) : 0,
            'detractor_pct' => $total > 0 ? round(($detractors / $total) * 100, 1) : 0,
        ];
    }

    public function getSatisfactionTrend(int $days = 365): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $rows = DB::table('survey_votes')
            ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
            ->join('surveys', 'survey_questions.survey_id', '=', 'surveys.id')
            ->whereBetween('surveys.published_at', [$startDate, Carbon::now()])
            ->select('survey_votes.text_value as score', 'surveys.published_at')
            ->orderBy('surveys.published_at')
            ->get();

        $scoresByMonth = $rows->groupBy(function ($row) {
            return Carbon::parse($row->published_at)->format('M Y');
        })->map(function ($votes) {
            $numeric = $votes->map(function ($v) {
                return $this->parseTextToScore($v->score);
            })->filter(fn($v) => is_numeric($v))->map(fn($v) => (float) $v);
            return $numeric->isEmpty() ? 0 : $numeric->avg();
        });

        $labels = $scoresByMonth->keys()->toArray();
        $data = $scoresByMonth->map(fn($v) => round($v, 2))->values()->toArray();

        return ['labels' => $labels, 'data' => $data];
    }

    public function getSurveySummary(int $days = 90): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $surveys = DB::table('surveys')
            ->whereBetween('surveys.published_at', [$startDate, Carbon::now()])
            ->select('id', 'title', 'published_at', 'expires_at')
            ->orderByDesc('published_at')
            ->get();

        $summaries = [];
        $surveys->each(function ($survey) use (&$summaries) {
            $responseRate = $this->getResponseRate($survey->id);
            $nps = $this->getNPS($survey->id);

            $avgScore = DB::table('survey_votes')
                ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
                ->where('survey_questions.survey_id', $survey->id)
                ->selectRaw('AVG(CAST(survey_votes.text_value AS DECIMAL(10,2))) as avg_score')
                ->value('avg_score') ?? 0;

            $summaries[] = [
                'id' => $survey->id,
                'title' => $survey->title,
                'start_date' => $survey->published_at,
                'end_date' => $survey->expires_at,
                'avg_score' => round((float) $avgScore, 2),
                'response_rate' => $responseRate['rate'],
                'nps' => $nps['nps'],
                'responded' => $responseRate['responded'],
            ];
        });

        return $summaries;
    }

    public function getResponseDistribution(int $surveyId, int $questionId): array
    {
        $numericValues = DB::table('survey_votes')
            ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
            ->where('survey_questions.survey_id', $surveyId)
            ->where('survey_votes.question_id', $questionId)
            ->select('survey_votes.text_value as text_value')
            ->pluck('text_value')
            ->map(function ($v) {
                return $this->parseTextToScore($v);
            })->filter()->values();

        $total = $numericValues->count();

        $distribution = $numericValues->count() > 0
            ? $numericValues->mapWithKey(fn($v, $k) => ['value' => $v, 'count' => $numericValues->filter(fn($x) => $x === $v)->count()])
                ->sortBy('value')
                ->map(function ($item, $key) use ($total) {
                    return [
                        'value' => (int) $item['value'],
                        'count' => $item['count'],
                        'percentage' => $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0,
                    ];
                })->values()->toArray()
            : [];

        return $distribution;
    }

    /**
     * Parse text_value to numeric score.
     */
    private function parseTextToScore($value)
    {
        if ($value === null) return null;
        if (is_numeric($value)) return (float) $value;
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') return null;
            if (is_numeric($trimmed)) return (float) $trimmed;
        }
        return null;
    }

    public function calculateNPS(int $days = 180): array
    {
        $startDate = Carbon::now()->copy()->subDays($days);

        $numericVotes = DB::table('survey_votes')
            ->join('survey_questions', 'survey_votes.question_id', '=', 'survey_questions.id')
            ->join('surveys', 'survey_questions.survey_id', '=', 'surveys.id')
            ->where('survey_questions.type', 'nps')
            ->whereBetween('surveys.published_at', [$startDate, Carbon::now()])
            ->select('survey_votes.text_value')
            ->pluck('text_value')
            ->map(function ($v) {
                return $this->parseTextToScore($v);
            })->filter(fn($v) => is_numeric($v))->map(fn($v) => (float) $v);

        if ($numericVotes->isEmpty()) return ['nps' => 0, 'promoters' => 0, 'passives' => 0, 'detractors' => 0, 'total' => 0];

        $promoters = $numericVotes->filter(fn($v) => $v >= 9)->count();
        $passives = $numericVotes->filter(fn($v) => $v >= 7 && $v <= 8)->count();
        $detractors = $numericVotes->filter(fn($v) => $v <= 6)->count();
        $total = $numericVotes->count();

        $nps = $total > 0 ? round(($promoters - $detractors) / $total * 100, 1) : 0;

        return [
            'nps' => $nps,
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'total' => $total,
        ];
    }
}
