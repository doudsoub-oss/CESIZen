<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuItems\StoreMenuItemRequest;
use App\Http\Requests\Admin\MenuItems\UpdateMenuItemRequest;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuItemController extends Controller
{
    public function create(Menu $menu): Response
    {
        $this->authorize('create', MenuItem::class);

        return Inertia::render('admin/menu-items/Create', [
            'menu' => $menu,
            'parentOptions' => $this->parentOptions($menu),
            'contentOptions' => $this->contentOptions(),
        ]);
    }

    public function store(StoreMenuItemRequest $request, Menu $menu): RedirectResponse
    {
        $menu->items()->create([
            ...$request->validated(),
            // Content-linked items resolve their URL from the content itself.
            'url' => null,
        ]);

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', __('Entrée ajoutée.'));
    }

    public function edit(Menu $menu, MenuItem $item): Response
    {
        $this->authorize('update', $item);

        return Inertia::render('admin/menu-items/Edit', [
            'menu' => $menu,
            'item' => $item,
            'parentOptions' => $this->parentOptions($menu, excludeId: $item->id),
            'contentOptions' => $this->contentOptions(),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        $item->update([
            ...$request->validated(),
            // Content-linked items resolve their URL from the content itself.
            'url' => null,
        ]);

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', __('Entrée mise à jour.'));
    }

    public function destroy(Menu $menu, MenuItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', __('Entrée supprimée.'));
    }

    /**
     * @return array<int, array{id: int, title: string}>
     */
    private function parentOptions(Menu $menu, ?int $excludeId = null): array
    {
        return MenuItem::query()
            ->where('menu_id', $menu->id)
            ->when($excludeId, fn ($q, $id) => $q->whereKeyNot($id))
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (MenuItem $i) => ['id' => $i->id, 'title' => $i->title])
            ->all();
    }

    /**
     * Selectable site contents, labelled with their type for the picker.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function contentOptions(): array
    {
        return Content::query()
            ->orderBy('title')
            ->get(['id', 'title', 'type'])
            ->map(fn (Content $c) => [
                'id' => $c->id,
                'label' => $c->title.' ('.$c->type->label().')',
            ])
            ->all();
    }
}
