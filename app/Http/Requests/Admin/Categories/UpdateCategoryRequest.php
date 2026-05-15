<?php

namespace App\Http\Requests\Admin\Categories;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $this->user()?->can('update', $category) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable',
                'integer',
                'different:'.$category->id,
                'exists:categories,id',
                $this->noCycleRule($category),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Reject a parent that is the category itself or any of its descendants.
     * Prevents creating a cycle in the parent_id tree.
     */
    private function noCycleRule(Category $category): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($category): void {
            if ($value === null) {
                return;
            }

            $descendantIds = $this->descendantIds($category);

            if (in_array((int) $value, $descendantIds, true)) {
                $fail(__('Le parent choisi est un descendant de cette catégorie (cela créerait un cycle).'));
            }
        };
    }

    /**
     * @return array<int>
     */
    private function descendantIds(Category $category): array
    {
        $ids = [];
        $stack = [$category->id];

        while ($stack !== []) {
            $parentId = array_pop($stack);
            $childIds = Category::where('parent_id', $parentId)->pluck('id')->all();

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
