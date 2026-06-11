<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_view_the_audit_trail(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_the_audit_trail(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/audit-logs/Index'));
    }

    public function test_the_action_filter_narrows_the_results(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Generate a couple of distinct actions.
        $category = Category::factory()->create();   // category.created
        $category->update(['name' => 'Renommée']);   // category.updated

        $this->get(route('admin.audit-logs.index', ['action' => 'category.created']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'category.created')
            );
    }
}
