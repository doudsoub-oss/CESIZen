<?php

namespace App\Http\Requests\Admin\Questions;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('question')) ?? false;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['boolean'],
        ];
    }
}
