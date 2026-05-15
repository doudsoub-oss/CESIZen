<?php

namespace App\Http\Controllers\Information;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class InformationController extends Controller
{
    /**
     * Public landing page — shows top-level active categories with a
     * preview of the most recent published contents in each.
     */
    public function index(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
                'contents' => fn ($q) => $q->published()->latest('published_at')->limit(3),
            ])
            ->orderBy('position')
            ->get();

        return Inertia::render('public/Information/Index', [
            'categories' => $categories,
        ]);
    }
}
