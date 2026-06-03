<?php

namespace Tests\Feature\Admin\Information;

use App\Enums\ContentType;
use App\Models\Category;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_regular_user_cannot_access_admin_contents(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.contents.index'))
            ->assertForbidden();
    }

    public function test_admin_can_store_an_article_in_a_category(): void
    {
        $admin = $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->post(route('admin.contents.store'), [
            'title' => 'Mon article',
            'body' => '<p>Hello</p>',
            'type' => ContentType::Article->value,
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.contents.index'));
        $this->assertDatabaseHas('contents', [
            'title' => 'Mon article',
            'slug' => 'mon-article',
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_article_without_category_is_rejected(): void
    {
        $this->actingAsAdmin();

        $response = $this->from(route('admin.contents.create'))
            ->post(route('admin.contents.store'), [
                'title' => 'Sans catégorie',
                'body' => 'x',
                'type' => ContentType::Article->value,
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_page_without_category_is_allowed(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.contents.store'), [
            'title' => 'Mentions légales',
            'body' => 'Texte légal',
            'type' => ContentType::Page->value,
        ])->assertRedirect(route('admin.contents.index'));

        $this->assertDatabaseHas('contents', [
            'slug' => 'mentions-legales',
            'category_id' => null,
        ]);
    }

    public function test_admin_can_update_and_delete_a_content(): void
    {
        $this->actingAsAdmin();
        $content = Content::factory()->page()->create(['title' => 'Old', 'slug' => 'old']);

        $this->put(route('admin.contents.update', $content), [
            'title' => 'New',
            'slug' => 'new',
            'body' => 'updated body',
            'type' => ContentType::Page->value,
            'is_published' => true,
        ])->assertRedirect(route('admin.contents.index'));

        $this->assertDatabaseHas('contents', ['id' => $content->id, 'slug' => 'new']);

        $this->delete(route('admin.contents.destroy', $content))
            ->assertRedirect(route('admin.contents.index'));
        $this->assertDatabaseMissing('contents', ['id' => $content->id]);
    }

    public function test_admin_can_unpublish_a_content_via_update(): void
    {
        $this->actingAsAdmin();
        $content = Content::factory()->page()->create([
            'slug' => 'legal',
            'is_published' => true,
        ]);

        // The "Publié" checkbox is unticked, so `is_published` is absent from
        // the request — it must still persist as false.
        $this->put(route('admin.contents.update', $content), [
            'title' => $content->title,
            'slug' => 'legal',
            'body' => 'corps',
            'type' => ContentType::Page->value,
        ])->assertRedirect(route('admin.contents.index'));

        $this->assertFalse($content->fresh()->is_published);
    }

    public function test_publishing_stamps_published_at_automatically_and_ignores_user_input(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        // A client-supplied published_at must be ignored in favour of the server clock.
        $this->post(route('admin.contents.store'), [
            'title' => 'Daté',
            'body' => 'corps',
            'type' => ContentType::Article->value,
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => '2000-01-01',
        ])->assertRedirect(route('admin.contents.index'));

        $content = Content::where('slug', 'date')->firstOrFail();
        $this->assertNotNull($content->published_at);
        $this->assertTrue($content->published_at->isToday());
    }

    public function test_storing_an_unpublished_content_leaves_published_at_null(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.contents.store'), [
            'title' => 'Brouillon',
            'body' => 'corps',
            'type' => ContentType::Page->value,
            'is_published' => false,
        ])->assertRedirect(route('admin.contents.index'));

        $content = Content::where('slug', 'brouillon')->firstOrFail();
        $this->assertNull($content->published_at);
    }

    public function test_publishing_via_update_sets_published_at(): void
    {
        $this->actingAsAdmin();
        $content = Content::factory()->page()->create([
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->put(route('admin.contents.update', $content), [
            'title' => $content->title,
            'slug' => $content->slug,
            'body' => 'corps',
            'type' => ContentType::Page->value,
            'is_published' => true,
        ])->assertRedirect(route('admin.contents.index'));

        $this->assertNotNull($content->fresh()->published_at);
        $this->assertTrue($content->fresh()->published_at->isToday());
    }

    public function test_unpublishing_via_update_clears_published_at(): void
    {
        $this->actingAsAdmin();
        $content = Content::factory()->page()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        // Checkbox unticked ⇒ is_published absent ⇒ content is unpublished.
        $this->put(route('admin.contents.update', $content), [
            'title' => $content->title,
            'slug' => $content->slug,
            'body' => 'corps',
            'type' => ContentType::Page->value,
        ])->assertRedirect(route('admin.contents.index'));

        $this->assertNull($content->fresh()->published_at);
    }
}
