<?php

namespace Tests\Feature\Admin\Information;

use App\Models\Category;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_crud_a_menu(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.menus.store'), [
            'name' => 'Footer',
            'location' => 'footer',
        ])->assertRedirect(route('admin.menus.index'));

        $menu = Menu::where('name', 'Footer')->firstOrFail();

        $this->put(route('admin.menus.update', $menu), [
            'name' => 'Pied de page',
            'location' => 'footer',
        ])->assertRedirect(route('admin.menus.index'));
        $this->assertSame('Pied de page', $menu->fresh()->name);

        $this->delete(route('admin.menus.destroy', $menu));
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_admin_can_add_a_content_linked_menu_item(): void
    {
        $this->actingAsAdmin();
        $menu = Menu::factory()->main()->create();
        $content = Content::factory()->create();

        $this->post(route('admin.menus.items.store', $menu), [
            'title' => 'Accueil',
            'content_id' => $content->id,
            'position' => 0,
            'is_active' => true,
        ])->assertRedirect(route('admin.menus.edit', $menu));

        // The raw URL is cleared: the link is resolved from the linked content.
        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'Accueil',
            'content_id' => $content->id,
            'url' => null,
        ]);
    }

    public function test_menu_item_without_a_content_is_rejected(): void
    {
        $this->actingAsAdmin();
        $menu = Menu::factory()->main()->create();

        $response = $this->from(route('admin.menus.items.create', $menu))
            ->post(route('admin.menus.items.store', $menu), [
                'title' => 'Vide',
            ]);

        $response->assertSessionHasErrors('content_id');
    }

    public function test_menu_item_parent_must_belong_to_same_menu(): void
    {
        $this->actingAsAdmin();
        $menuA = Menu::factory()->main()->create();
        $menuB = Menu::factory()->footer()->create();
        $itemInB = MenuItem::factory()->create(['menu_id' => $menuB->id]);
        $content = Content::factory()->create();

        $response = $this->from(route('admin.menus.items.create', $menuA))
            ->post(route('admin.menus.items.store', $menuA), [
                'title' => 'Cross',
                'content_id' => $content->id,
                'parent_id' => $itemInB->id,
            ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_menu_item_parent_cycle_is_rejected(): void
    {
        $this->actingAsAdmin();
        $menu = Menu::factory()->main()->create();
        $content = Content::factory()->create();
        $parent = MenuItem::factory()->create(['menu_id' => $menu->id]);
        $child = MenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => $parent->id]);

        $response = $this->from(route('admin.menus.items.edit', [$menu, $parent]))
            ->put(route('admin.menus.items.update', [$menu, $parent]), [
                'title' => $parent->title,
                'content_id' => $content->id,
                'parent_id' => $child->id,
            ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_menu_item_url_is_resolved_from_its_linked_content(): void
    {
        $category = Category::factory()->create(['slug' => 'sante']);
        $content = Content::factory()->create([
            'category_id' => $category->id,
            'slug' => 'gerer-son-stress',
            'is_published' => true,
        ]);
        $menu = Menu::factory()->main()->create();
        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Gérer son stress',
            'url' => null,
            'content_id' => $content->id,
            'is_active' => true,
        ]);

        // The shared navigation payload builds the URL from the content's category + slug.
        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation.main', fn ($items) => collect($items)->contains(
                fn ($i) => str_contains((string) $i['url'], '/informations/sante/gerer-son-stress')
            ))
        );
    }
}
