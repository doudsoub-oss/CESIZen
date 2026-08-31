<?php

namespace Tests\Feature\Securite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Couvre la limitation de débit du lot L03 (traite R6) : plafonnement des
 * tentatives d'authentification et de réinitialisation, PAR ADRESSE et PAR
 * COMPTE, avec une réponse 429 générique qui ne révèle pas l'existence d'un compte.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private function loginAttempt(string $email, string $ip, string $password = 'wrong-password')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('login.store'), [
                'email' => $email,
                'password' => $password,
            ]);
    }

    public function test_login_is_blocked_by_ip_after_five_attempts(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        for ($i = 1; $i <= 5; $i++) {
            $this->loginAttempt($user->email, '203.0.113.10')->assertStatus(302);
        }

        // La 6e tentative depuis la même IP est refusée.
        $this->loginAttempt($user->email, '203.0.113.10')->assertTooManyRequests();
    }

    public function test_login_is_blocked_by_account_across_different_ips(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        // 10 tentatives sur le même compte depuis 10 IP différentes : la limite
        // par IP (5/min) n'est jamais atteinte, mais la limite par compte l'est.
        for ($i = 1; $i <= 10; $i++) {
            $this->loginAttempt($user->email, "198.51.100.{$i}")
                ->assertStatus(302);
        }

        // La 11e tentative, depuis une IP encore différente, est refusée.
        $this->loginAttempt($user->email, '198.51.100.200')->assertTooManyRequests();
    }

    public function test_password_reset_request_is_blocked_after_three_for_same_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
                ->post(route('password.email'), ['email' => $user->email])
                ->assertStatus(302);
        }

        // La 4e demande de réinitialisation pour le même email est refusée.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->post(route('password.email'), ['email' => $user->email])
            ->assertTooManyRequests();
    }

    public function test_registration_is_blocked_after_five_from_same_ip(): void
    {
        // Données volontairement invalides (mot de passe sans complexité) : chaque
        // tentative échoue à la validation sans authentifier, donc reste « invité »
        // et incrémente bien le compteur de débit.
        $attempt = fn (int $i) => $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->post(route('register.store'), [
                'name' => "Usager {$i}",
                'email' => "usager{$i}@exemple.test",
                'password' => 'faible',
                'password_confirmation' => 'faible',
            ]);

        for ($i = 1; $i <= 5; $i++) {
            $attempt($i)->assertStatus(302);
        }

        // La 6e inscription depuis la même IP est refusée.
        $attempt(6)->assertTooManyRequests();
    }

    public function test_counter_resets_after_the_window(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $user = User::factory()->create(['is_active' => true]);

        for ($i = 1; $i <= 5; $i++) {
            $this->loginAttempt($user->email, '203.0.113.40');
        }
        $this->loginAttempt($user->email, '203.0.113.40')->assertTooManyRequests();

        // Passée la fenêtre d'une minute, le compteur par IP est réinitialisé.
        Carbon::setTestNow('2026-08-31 10:01:30');

        $this->loginAttempt($user->email, '203.0.113.40')->assertStatus(302);

        Carbon::setTestNow();
    }

    public function test_legitimate_authentication_is_not_blocked_below_thresholds(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('Correct-horse-1!'),
        ]);

        $response = $this->loginAttempt($user->email, '203.0.113.50', 'Correct-horse-1!');

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_throttled_response_does_not_reveal_whether_the_account_exists(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $knownEmail = $user->email;

        // Saturer le seuil par IP avec un compte connu et un compte inconnu :
        // la réponse 429 doit être identique dans les deux cas.
        $knownResponse = null;
        for ($i = 1; $i <= 6; $i++) {
            $knownResponse = $this->loginAttempt($knownEmail, '203.0.113.60');
        }

        $unknownResponse = null;
        for ($i = 1; $i <= 6; $i++) {
            $unknownResponse = $this->loginAttempt('inconnu@exemple.test', '203.0.113.61');
        }

        $knownResponse->assertTooManyRequests();
        $unknownResponse->assertTooManyRequests();
    }
}
