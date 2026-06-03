<?php

namespace App\Http\Requests\Admin\MenuItems;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MenuItem::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Normalize the checkbox so an unchecked box submits a real `false`.
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        /** @var Menu $menu */
        $menu = $this->route('menu');

        return [
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'content_id' => ['nullable', 'integer', 'exists:contents,id'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id')->where(fn ($q) => $q->where('menu_id', $menu->id)),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    /** Cross-field check: a menu item must have a target. */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('url') && ! $this->filled('content_id')) {
                    $validator->errors()->add(
                        'target',
                        __('Une entrée de menu doit pointer vers une URL ou un contenu.')
                    );
                }
            },
        ];
    }
}
