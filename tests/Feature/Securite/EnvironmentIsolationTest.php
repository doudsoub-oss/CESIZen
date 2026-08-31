<?php

namespace Tests\Feature\Securite;

use App\Models\Diagnostic;
use App\Models\User;
use App\Support\EnvironmentGuard;
use Database\Seeders\RecetteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Couvre le cloisonnement des environnements (lot L06, traite R10) : garde-fou
 * de débogage, non-indexation de la recette, et refus du seeder de recette en
 * production.
 */
class EnvironmentIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_guard_blocks_startup_in_deployed_environments(): void
    {
        foreach (['production', 'staging', 'recette'] as $environment) {
            try {
                EnvironmentGuard::ensureDebugIsDisabled($environment, true);
                $this->fail("Le garde-fou aurait dû lever une exception en « {$environment} ».");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString($environment, $e->getMessage());
            }
        }
    }

    public function test_debug_guard_allows_debug_in_local_and_testing(): void
    {
        EnvironmentGuard::ensureDebugIsDisabled('local', true);
        EnvironmentGuard::ensureDebugIsDisabled('testing', true);

        $this->expectNotToPerformAssertions();
    }

    public function test_debug_guard_allows_deployed_environments_without_debug(): void
    {
        EnvironmentGuard::ensureDebugIsDisabled('production', false);
        EnvironmentGuard::ensureDebugIsDisabled('recette', false);

        $this->expectNotToPerformAssertions();
    }

    public function test_recette_responses_carry_noindex_header(): void
    {
        $this->app['env'] = 'recette';

        // Requête HTTPS : la recette force le HTTPS (L04), une requête en clair
        // serait redirigée avant d'atteindre les en-têtes.
        $response = $this->get('https://localhost/login');

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->app['env'] = 'testing';
    }

    public function test_noindex_header_is_absent_outside_recette(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_robots_txt_disallows_everything_in_recette(): void
    {
        $this->app['env'] = 'recette';

        $this->get('https://localhost/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /');

        $this->app['env'] = 'testing';
    }

    public function test_robots_txt_is_permissive_outside_recette(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringContainsString("Disallow:\n", $response->getContent());
        $this->assertStringNotContainsString('Disallow: /', $response->getContent());
    }

    public function test_recette_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->expectException(RuntimeException::class);
            (new RecetteSeeder)->run();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_recette_seeder_produces_a_fictitious_dataset(): void
    {
        (new RecetteSeeder)->run();

        // Comptes créés, tous avec des adresses non routables.
        $this->assertGreaterThanOrEqual(10, User::count());
        $this->assertTrue(
            User::query()->pluck('email')->every(
                fn (string $email) => str_ends_with($email, '@example.org')
                    || str_ends_with($email, '@example.net')
                    || str_ends_with($email, '@example.com')
            ),
            'Toutes les adresses doivent être non routables (safeEmail).'
        );

        // Diagnostics rattachés à des comptes.
        $this->assertGreaterThan(0, Diagnostic::count());
    }
}
