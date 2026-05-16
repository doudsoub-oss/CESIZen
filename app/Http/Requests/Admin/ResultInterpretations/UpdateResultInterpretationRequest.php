<?php

namespace App\Http\Requests\Admin\ResultInterpretations;

use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResultInterpretationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('interpretation')) ?? false;
    }

    public function rules(): array
    {
        return [
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'gte:min_score'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'recommendations' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Questionnaire $questionnaire */
                $questionnaire = $this->route('questionnaire');
                /** @var ResultInterpretation $current */
                $current = $this->route('interpretation');

                $min = (int) $this->input('min_score');
                $max = (int) $this->input('max_score');

                $overlap = ResultInterpretation::query()
                    ->where('questionnaire_id', $questionnaire->id)
                    ->whereKeyNot($current->id)
                    ->where('min_score', '<=', $max)
                    ->where('max_score', '>=', $min)
                    ->exists();

                if ($overlap) {
                    $validator->errors()->add(
                        'min_score',
                        __('Cette plage chevauche une interprétation existante pour ce questionnaire.')
                    );
                }
            },
        ];
    }
}
