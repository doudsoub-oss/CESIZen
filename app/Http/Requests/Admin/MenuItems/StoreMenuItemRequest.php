<?php

namespace App\Http\Requests\Admin\MenuItems;

use App\Models\Menu;
use App\Models\MenuItem;
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
            // A menu item targets a site content; its URL is resolved from the content.
            'content_id' => ['required', 'integer', 'exists:contents,id'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id')->where(fn ($q) => $q->where('menu_id', $menu->id)),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
