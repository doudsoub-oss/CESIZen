<?php

namespace App\Http\Requests\Admin\Questionnaires;

use App\Models\Questionnaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('questionnaire')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

    public function rules(): array
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->route('questionnaire');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('questionnaires', 'slug')->ignore($questionnaire->id),
            ],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
