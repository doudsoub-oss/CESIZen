<?php

namespace App\Http\Requests\Admin\AnswerOptions;

use App\Models\AnswerOption;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AnswerOption::class) ?? false;
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
