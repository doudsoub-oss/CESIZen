<?php

namespace App\Http\Controllers\Information;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentController extends Controller
{
    /**
     * Canonical viewer for a content piece that lives inside a category.
     * Route is scope-bound, so `$content` is guaranteed to belong to `$category`.
     */
    public function show(Category $category, Content $content): Response
    {
        if (! $category->is_active || ! $this->isContentPublic($content)) {
            throw new NotFoundHttpException;
        }

        $content->load(['category', 'author']);

        return Inertia::render('public/Information/Content', [
            'category' => $category,
            'content' => $content,
        ]);
    }

    /**
     * Canonical viewer for uncategorised contents (typically `type=page`,
     * e.g. mentions légales). Also serves as a generic slug-based viewer.
     */
    public function page(Content $content): Response
    {
        if (! $this->isContentPublic($content)) {
            throw new NotFoundHttpException;
        }

        $content->load(['category', 'author']);

        return Inertia::render('public/Pages/Show', [
            'content' => $content,
        ]);
    }

    private function isContentPublic(Content $content): bool
    {
        if (! $content->is_published) {
            return false;
        }

        return $content->published_at === null || $content->published_at->isPast();
    }
}
