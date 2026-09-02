<?php

namespace App\Http\Requests\Diagnostic;

use App\Models\Questionnaire;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SubmitDiagnosticRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Questionnaire|null $questionnaire */
        $questionnaire = $this->route('questionnaire');

        return $questionnaire !== null && $questionnaire->is_active;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer'],
            // Consentement explicite au rattachement du résultat au compte
            // (art. 9.2.a). Facultatif : son absence signifie « ne pas conserver ».
            'consent' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Cross-field check: every required question must have an answer, and
     * every option supplied must belong to its question (and to this
     * questionnaire).
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Questionnaire $questionnaire */
                $questionnaire = $this->route('questionnaire');

                $answers = collect($this->input('answers', []))
                    ->mapWithKeys(fn ($v, $k) => [(int) $k => (int) $v]);

                $questions = $questionnaire->questions()->with('answerOptions:id,question_id')->get();

                // Every required question must have an answer.
                foreach ($questions as $question) {
                    if ($question->is_required && ! $answers->has($question->id)) {
                        $validator->errors()->add(
                            "answers.{$question->id}",
                            __('Cette question est obligatoire.')
                        );
                    }
                }

                // Every supplied (question, option) pair must belong together.
                $questionMap = $questions->keyBy('id');
                foreach ($answers as $questionId => $optionId) {
                    $question = $questionMap->get($questionId);
                    if ($question === null) {
                        $validator->errors()->add(
                            "answers.{$questionId}",
                            __('Question inconnue pour ce questionnaire.')
                        );

                        continue;
                    }

                    $belongs = $question->answerOptions->contains('id', $optionId);
                    if (! $belongs) {
                        $validator->errors()->add(
                            "answers.{$questionId}",
                            __('Cette réponse n\'appartient pas à la question.')
                        );
                    }
                }
            },
        ];
    }
}
