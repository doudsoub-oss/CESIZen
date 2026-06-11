<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Tests\TestCase;

class AuthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_audited(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_login_is_audited_without_a_user_but_keeps_the_email(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $log = AuditLog::query()->where('action', 'auth.failed')->firstOrFail();

        $this->assertNull($log->user_id);
        $this->assertSame($user->email, $log->new_values['email']);
    }

    public function test_logout_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_password_reset_event_is_audited(): void
    {
        $user = User::factory()->create();

        event(new PasswordReset($user));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset',
            'user_id' => $user->id,
        ]);
    }

    public function test_two_factor_enable_and_disable_events_are_audited(): void
    {
        $user = User::factory()->create();

        event(new TwoFactorAuthenticationEnabled($user));
        event(new TwoFactorAuthenticationDisabled($user));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.two_factor_enabled',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.two_factor_disabled',
            'user_id' => $user->id,
        ]);
    }
}
