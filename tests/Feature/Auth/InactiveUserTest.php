<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_inactive_user_is_blocked_at_login_with_a_field_error(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_who_becomes_inactive_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Simulate an admin deactivating the account.
        $user->forceFill(['is_active' => false])->save();

        // EnsureUserIsActive runs on every web request, so any authenticated
        // navigation (here the settings profile page) bounces the account.
        $response = $this->get(route('profile.edit'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
