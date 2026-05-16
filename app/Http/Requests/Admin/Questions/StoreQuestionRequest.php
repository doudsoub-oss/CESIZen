<?php

namespace App\Http\Requests\Admin\Questions;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Question::class) ?? false;
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
