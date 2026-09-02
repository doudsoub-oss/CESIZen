<?php

namespace Tests\Feature\Rgpd;

use App\Models\Diagnostic;
use App\Models\Questionnaire;
use App\Models\User;
use App\Notifications\AccountInactivityWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Durées de conservation et purges (L09, Tableau 12) — bornes vérifiées avec
 * Carbon::setTestNow.
 */
class ConservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-31 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function inactiveUser(int $months, ?\DateTimeInterface $notifiedAt = null): User
    {
        return User::factory()->create([
            'last_login_at' => now()->subMonths($months)->subDay(),
            'inactivity_notified_at' => $notifiedAt,
        ]);
    }

    public function test_account_at_22_months_is_neither_warned_nor_purged(): void
    {
        Notification::fake();
        $user = $this->inactiveUser(22);

        $this->artisan('comptes:preavis-inactivite')->assertSuccessful();
        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($user->fresh()->inactivity_notified_at);
        $this->assertModelExists($user);
    }

    public function test_account_at_23_months_receives_a_single_warning(): void
    {
        Notification::fake();
        $user = $this->inactiveUser(23);

        $this->artisan('comptes:preavis-inactivite')->assertSuccessful();
        // Deuxième passage : ne doit pas renvoyer le préavis.
        $this->artisan('comptes:preavis-inactivite')->assertSuccessful();

        Notification::assertSentToTimes($user, AccountInactivityWarning::class, 1);
        $this->assertNotNull($user->fresh()->inactivity_notified_at);
    }

    public function test_account_at_24_months_without_warning_is_not_purged(): void
    {
        $user = $this->inactiveUser(24, notifiedAt: null);

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelExists($user);
    }

    public function test_account_at_24_months_with_warning_is_purged_with_cascades(): void
    {
        $user = $this->inactiveUser(24, notifiedAt: now()->subMonth());
        $questionnaire = Questionnaire::factory()->create();
        Diagnostic::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelMissing($user);
        $this->assertDatabaseCount('diagnostics', 0);
    }

    public function test_purge_dry_run_does_not_delete(): void
    {
        $user = $this->inactiveUser(24, notifiedAt: now()->subMonth());

        $this->artisan('comptes:purger-inactifs', ['--dry-run' => true])->assertSuccessful();

        $this->assertModelExists($user);
    }

    public function test_audit_log_retention_is_twelve_months(): void
    {
        DB::table('audit_logs')->insert([
            ['action' => 'test.recent', 'created_at' => now()->subMonths(11)],
            ['action' => 'test.old', 'created_at' => now()->subMonths(13)],
        ]);

        $this->artisan('audit:purger')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['action' => 'test.recent']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'test.old']);
    }

    public function test_login_updates_last_login_at(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('Password1!'),
            'last_login_at' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
