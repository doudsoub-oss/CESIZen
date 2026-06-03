<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Contents\StoreContentRequest;
use App\Http\Requests\Admin\Contents\UpdateContentRequest;
use App\Models\Category;
use App\Models\Content;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Content::class);

        $contents = Content::query()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->latest('updated_at')
            ->paginate(20);

        return Inertia::render('admin/contents/Index', [
            'contents' => $contents,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Content::class);

        return Inertia::render('admin/contents/Create', [
            'categories' => $this->categoryOptions(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Content::create([
            ...$validated,
            // The publication timestamp is derived from the publish state, never client-supplied.
            'published_at' => $validated['is_published'] ? now() : null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.contents.index')
            ->with('status', __('Contenu créé.'));
    }

    public function edit(Content $content): Response
    {
        $this->authorize('update', $content);

        return Inertia::render('admin/contents/Edit', [
            'content' => $content,
            'categories' => $this->categoryOptions(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateContentRequest $request, Content $content): RedirectResponse
    {
        $validated = $request->validated();

        $content->update([
            ...$validated,
            // Stamp on first publish, keep the original date on re-save, clear when unpublished.
            'published_at' => $validated['is_published']
                ? ($content->published_at ?? now())
                : null,
        ]);

        return redirect()
            ->route('admin.contents.index')
            ->with('status', __('Contenu mis à jour.'));
    }

    public function destroy(Content $content): RedirectResponse
    {
        $this->authorize('delete', $content);

        $content->delete();

        return redirect()
            ->route('admin.contents.index')
            ->with('status', __('Contenu supprimé.'));
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return collect(ContentType::cases())
            ->map(fn (ContentType $t) => ['value' => $t->value, 'label' => $t->label()])
            ->all();
    }
}
