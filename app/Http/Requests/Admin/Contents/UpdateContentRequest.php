<?php

namespace App\Http\Requests\Admin\Contents;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $content = $this->route('content');

        return $this->user()?->can('update', $content) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }

        // Normalize the checkbox so an unchecked box submits a real `false`.
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    public function rules(): array
    {
        /** @var Content $content */
        $content = $this->route('content');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('contents', 'slug')->ignore($content->id),
            ],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'type' => ['required', Rule::enum(ContentType::class)],
            'category_id' => [
                'nullable', 'integer', 'exists:categories,id',
                Rule::requiredIf(fn () => $this->input('type') !== ContentType::Page->value),
            ],
            'is_published' => ['boolean'],
        ];
    }
}
