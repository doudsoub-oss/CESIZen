<?php

namespace App\Http\Requests\Admin\Questionnaires;

use App\Models\Questionnaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Questionnaire::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('questionnaires', 'slug')],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
