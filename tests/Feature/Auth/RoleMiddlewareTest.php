<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])
            ->get('/_test/admin-only', fn () => response('ok'));

        Route::middleware(['web', 'auth', 'role:super_admin'])
            ->get('/_test/super-admin-only', fn () => response('ok'));
    }

    public function test_guest_hitting_role_protected_route_is_redirected_to_login(): void
    {
        $this->get('/_test/admin-only')->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_admin_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/_test/admin-only')
            ->assertForbidden();
    }

    public function test_admin_is_allowed_through_admin_role_check(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/_test/admin-only')
            ->assertOk();
    }

    public function test_super_admin_is_allowed_through_admin_role_check(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/_test/admin-only')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_super_admin_only_route(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/_test/super-admin-only')
            ->assertForbidden();
    }

    public function test_super_admin_is_allowed_through_super_admin_only_route(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/_test/super-admin-only')
            ->assertOk();
    }
}
