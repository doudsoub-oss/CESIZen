<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount('contents')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->paginate(20);

        return Inertia::render('admin/categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Category::class);

        return Inertia::render('admin/categories/Create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('Catégorie créée.'));
    }

    public function edit(Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('admin/categories/Edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('Catégorie mise à jour.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('Catégorie supprimée.'));
    }

    public function toggleActive(Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status', __('Visibilité mise à jour.'));
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function parentOptions(?int $excludeId = null): array
    {
        return Category::query()
            ->when($excludeId, fn ($q, $id) => $q->whereKeyNot($id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }
}
