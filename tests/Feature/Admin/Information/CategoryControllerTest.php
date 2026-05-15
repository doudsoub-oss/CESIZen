<?php

namespace Tests\Feature\Admin\Information;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_guest_is_redirected_from_admin_categories(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_the_index(): void
    {
        $this->actingAsAdmin();
        Category::factory(3)->create();

        $this->get(route('admin.categories.index'))->assertOk();
    }

    public function test_admin_can_store_a_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Nouvelle Catégorie',
            'slug' => '',
            'position' => 0,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Nouvelle Catégorie',
            'slug' => 'nouvelle-categorie',
        ]);
    }

    public function test_slug_is_auto_generated_from_name_when_empty(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.categories.store'), [
            'name' => 'Santé Mentale Avancée',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'sante-mentale-avancee']);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['slug' => 'taken']);

        $response = $this->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Other',
                'slug' => 'taken',
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_a_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Renamed',
            'slug' => 'renamed',
            'is_active' => true,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'renamed']);
    }

    public function test_parent_cycle_is_rejected_on_update(): void
    {
        $this->actingAsAdmin();
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);

        $response = $this->from(route('admin.categories.edit', $root))
            ->put(route('admin.categories.update', $root), [
                'name' => $root->name,
                'slug' => $root->slug,
                'parent_id' => $child->id,
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_admin_can_delete_a_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['is_active' => true]);

        $this->patch(route('admin.categories.toggle-active', $category));

        $this->assertFalse($category->fresh()->is_active);
    }
}
