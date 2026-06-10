<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_user_facing_dashboard_route_no_longer_exists(): void
    {
        // Regular users (and guests) have no dashboard; the only dashboard is
        // the admin back-office, gated by the `role:admin` middleware.
        $this->assertFalse(Route::has('dashboard'));
    }

    public function test_authenticated_users_land_on_the_public_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();
    }
}
