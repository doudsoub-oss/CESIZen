<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'navigation' => fn () => $this->navigationPayload(),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Resolve the public navigation payload (main + footer menus, with
     * each item's URL already resolved to a string).
     *
     * @return array{main: array<int, array<string, mixed>>, footer: array<int, array<string, mixed>>}
     */
    private function navigationPayload(): array
    {
        $menus = Menu::query()
            ->whereIn('location', ['main', 'footer'])
            ->with(['rootItems' => function ($query): void {
                $query->where('is_active', true)
                    ->with([
                        'children' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
                        'content.category',
                    ]);
            }])
            ->get()
            ->keyBy('location');

        return [
            'main' => $this->mapItems($menus->get('main')?->rootItems ?? collect()),
            'footer' => $this->mapItems($menus->get('footer')?->rootItems ?? collect()),
        ];
    }

    /**
     * @param  iterable<MenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(iterable $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $out[] = [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $this->resolveItemUrl($item),
                'children' => $this->mapItems($item->children),
            ];
        }

        return $out;
    }

    private function resolveItemUrl(MenuItem $item): ?string
    {
        // Stored URL wins.
        if (! empty($item->getRawOriginal('url'))) {
            return $item->getRawOriginal('url');
        }

        // Otherwise resolve from a linked content (uncategorised ⇒ page route).
        if ($item->content) {
            if ($item->content->category) {
                return route('informations.content', [
                    'category' => $item->content->category->slug,
                    'content' => $item->content->slug,
                ]);
            }

            return route('pages.show', $item->content->slug);
        }

        return null;
    }
}
