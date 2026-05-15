<?php

namespace App\Http\Requests\Admin\MenuItems;

use App\Models\Menu;
use App\Models\MenuItem;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        /** @var Menu $menu */
        $menu = $this->route('menu');
        /** @var MenuItem $item */
        $item = $this->route('item');

        return [
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'content_id' => ['nullable', 'integer', 'exists:contents,id'],
            'parent_id' => [
                'nullable',
                'integer',
                'different:'.$item->id,
                Rule::exists('menu_items', 'id')->where(fn ($q) => $q->where('menu_id', $menu->id)),
                $this->noCycleRule($item),
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

    private function noCycleRule(MenuItem $item): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($item): void {
            if ($value === null) {
                return;
            }

            $descendantIds = $this->descendantIds($item);

            if (in_array((int) $value, $descendantIds, true)) {
                $fail(__('Le parent choisi est un descendant de cette entrée (cela créerait un cycle).'));
            }
        };
    }

    /**
     * @return array<int>
     */
    private function descendantIds(MenuItem $item): array
    {
        $ids = [];
        $stack = [$item->id];

        while ($stack !== []) {
            $parentId = array_pop($stack);
            $childIds = MenuItem::where('parent_id', $parentId)->pluck('id')->all();

            foreach ($childIds as $childId) {
                if (! in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $stack[] = $childId;
                }
            }
        }

        return $ids;
    }
}
