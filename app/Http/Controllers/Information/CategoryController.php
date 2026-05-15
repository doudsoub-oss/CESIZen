<?php

namespace App\Http\Controllers\Information;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends Controller
{
    /**
     * Public category detail — lists published contents in this category
     * plus its active sub-categories.
     */
    public function show(Category $category): Response
    {
        if (! $category->is_active) {
            throw new NotFoundHttpException;
        }

        $category->load([
            'parent',
            'children' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
            'contents' => fn ($q) => $q->published()->latest('published_at'),
        ]);

        return Inertia::render('public/Information/Category', [
            'category' => $category,
        ]);
    }
}
