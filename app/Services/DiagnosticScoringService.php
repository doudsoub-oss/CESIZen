<?php

namespace App\Services;

use App\Models\AnswerOption;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use InvalidArgumentException;

class DiagnosticScoringService
{
    /**
     * Score a set of answers against a questionnaire and look up the
     * matching result interpretation.
     *
     * The input is a map of `question_id => answer_option_id`. All options
     * are validated to belong to their question and to this questionnaire;
     * any mismatch throws InvalidArgumentException.
     *
     * @param  array<int, int>  $answers  question_id ⇒ answer_option_id
     * @return array{total: int, interpretation: ?ResultInterpretation, responses: array<int, array{question_id: int, answer_option_id: int, score: int}>}
     */
    public function score(Questionnaire $questionnaire, array $answers): array
    {
        if ($answers === []) {
            return ['total' => 0, 'interpretation' => $this->interpretationForScore($questionnaire, 0), 'responses' => []];
        }

        $options = AnswerOption::query()
            ->whereIn('id', array_values($answers))
            ->with('question:id,questionnaire_id')
            ->get()
            ->keyBy('id');

        $total = 0;
        $responses = [];

        foreach ($answers as $questionId => $optionId) {
            $questionId = (int) $questionId;
            $optionId = (int) $optionId;

            $option = $options->get($optionId);
            if ($option === null) {
                throw new InvalidArgumentException("Unknown answer option [{$optionId}].");
            }

            if ($option->question_id !== $questionId) {
                throw new InvalidArgumentException(
                    "Option [{$optionId}] does not belong to question [{$questionId}]."
                );
            }

            if ($option->question->questionnaire_id !== $questionnaire->id) {
                throw new InvalidArgumentException(
                    "Question [{$questionId}] does not belong to questionnaire [{$questionnaire->id}]."
                );
            }

            $total += $option->score;
            $responses[] = [
                'question_id' => $questionId,
                'answer_option_id' => $optionId,
                'score' => $option->score,
            ];
        }

        return [
            'total' => $total,
            'interpretation' => $this->interpretationForScore($questionnaire, $total),
            'responses' => $responses,
        ];
    }

    public function interpretationForScore(Questionnaire $questionnaire, int $score): ?ResultInterpretation
    {
        return $questionnaire->interpretations()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }
}
