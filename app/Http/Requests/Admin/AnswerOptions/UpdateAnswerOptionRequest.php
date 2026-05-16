<?php

namespace App\Http\Requests\Admin\AnswerOptions;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnswerOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('answerOption')) ?? false;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'score' => ['required', 'integer'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
