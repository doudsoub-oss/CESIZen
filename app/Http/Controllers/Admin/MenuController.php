<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Menus\StoreMenuRequest;
use App\Http\Requests\Admin\Menus\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Menu::class);

        $menus = Menu::query()
            ->withCount('items')
            ->orderBy('location')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/menus/Index', [
            'menus' => $menus,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Menu::class);

        return Inertia::render('admin/menus/Create');
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        Menu::create($request->validated());

        return redirect()
            ->route('admin.menus.index')
            ->with('status', __('Menu créé.'));
    }

    public function edit(Menu $menu): Response
    {
        $this->authorize('update', $menu);

        $menu->load([
            'items' => fn ($q) => $q->orderBy('parent_id')->orderBy('position'),
            'items.content:id,title',
        ]);

        return Inertia::render('admin/menus/Edit', [
            'menu' => $menu,
        ]);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validated());

        return redirect()
            ->route('admin.menus.index')
            ->with('status', __('Menu mis à jour.'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $this->authorize('delete', $menu);

        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('status', __('Menu supprimé.'));
    }
}
