<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'privacy' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        // L'acceptation de la politique de confidentialité est horodatée (L08).
        $this->assertNotNull(User::where('email', 'test@example.com')->first()->privacy_accepted_at);
    }

    public function test_newly_registered_user_can_load_the_home_page()
    {
        $this->post(route('register.store'), [
            'name' => 'Fresh User',
            'email' => 'fresh@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'privacy' => true,
        ]);

        $this->assertAuthenticated();

        // The post-register redirect target must be reachable by the new user.
        $this->get(route('home'))->assertOk();
    }

    public function test_registration_requires_accepting_the_privacy_policy()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'No Consent',
            'email' => 'noconsent@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            // 'privacy' absent
        ]);

        $response->assertSessionHasErrors('privacy');
        $this->assertGuest();
    }

    public function test_registration_rejects_a_password_without_complexity()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
