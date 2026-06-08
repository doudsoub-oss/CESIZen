<?php

namespace App\Http\Requests\Admin\MenuItems;

use App\Models\Menu;
use App\Models\MenuItem;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
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
        /** @var MenuItem $item */
        $item = $this->route('item');

        return [
            'title' => ['required', 'string', 'max:255'],
            // A menu item targets a site content; its URL is resolved from the content.
            'content_id' => ['required', 'integer', 'exists:contents,id'],
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
