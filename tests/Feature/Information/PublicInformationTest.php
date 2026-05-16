<?php

namespace Tests\Feature\Information;

use App\Models\Category;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_informations_landing_is_publicly_accessible(): void
    {
        Category::factory()->create();

        $this->get(route('informations.index'))->assertOk();
    }

    public function test_active_category_page_is_accessible(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->get(route('informations.category', $category))->assertOk();
    }

    public function test_inactive_category_returns_404(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->get(route('informations.category', $category))->assertNotFound();
    }

    public function test_published_content_under_its_category_is_accessible(): void
    {
        $category = Category::factory()->create();
        $content = Content::factory()->article()->create([
            'category_id' => $category->id,
        ]);

        $this->get(route('informations.content', [$category, $content]))->assertOk();
    }

    public function test_content_under_wrong_category_returns_404(): void
    {
        $catA = Category::factory()->create();
        $catB = Category::factory()->create();
        $content = Content::factory()->article()->create(['category_id' => $catA->id]);

        $this->get(route('informations.content', [$catB, $content]))->assertNotFound();
    }

    public function test_unpublished_content_returns_404(): void
    {
        $category = Category::factory()->create();
        $content = Content::factory()
            ->article()
            ->unpublished()
            ->create(['category_id' => $category->id]);

        $this->get(route('informations.content', [$category, $content]))->assertNotFound();
    }

    public function test_future_dated_content_returns_404(): void
    {
        $category = Category::factory()->create();
        $content = Content::factory()->article()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('informations.content', [$category, $content]))->assertNotFound();
    }

    public function test_page_route_works_for_uncategorised_pages(): void
    {
        $content = Content::factory()->page()->create();

        $this->get(route('pages.show', $content))->assertOk();
    }

    public function test_page_route_returns_404_for_unpublished(): void
    {
        $content = Content::factory()->page()->unpublished()->create();

        $this->get(route('pages.show', $content))->assertNotFound();
    }
}
