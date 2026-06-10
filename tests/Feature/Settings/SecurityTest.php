<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_page_is_displayed()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Security')
                ->where('canManageTwoFactor', true)
                ->where('twoFactorEnabled', false),
            );
    }

    public function test_security_page_requires_password_confirmation_when_enabled()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_security_page_does_not_require_password_confirmation_when_disabled()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]);

        $this->actingAs($user)
            ->get(route('security.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Security'),
            );
    }

    public function test_security_page_renders_without_two_factor_when_feature_is_disabled()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('security.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Security')
                ->where('canManageTwoFactor', false)
                ->missing('twoFactorEnabled')
                ->missing('requiresConfirmation'),
            );
    }

    public function test_user_can_enable_confirm_and_disable_two_factor_authentication()
    {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        // Enable: a secret and recovery codes are generated but 2FA is not yet active.
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());

        // Confirm with a valid TOTP code derived from the freshly generated secret.
        $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->refresh()->hasEnabledTwoFactorAuthentication());

        // Disable: the secret is cleared and 2FA is no longer active.
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('two-factor.disable'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
        $this->assertNull($user->two_factor_secret);
    }

    public function test_password_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('security.edit'));

        $this->assertTrue(Hash::check('NewPassword1!', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('security.edit'));
    }
}
