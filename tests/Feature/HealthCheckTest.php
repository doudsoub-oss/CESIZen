<?php

namespace Tests\Feature;

use App\Listeners\VerifierAccesBaseDeDonnees;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_endpoint_returns_ok_when_database_is_reachable(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_listener_throws_when_database_is_unreachable(): void
    {
        // Simule une base injoignable : le contrôle de santé doit propager
        // l'échec (→ /up répond 500, exploité par la sonde L20).
        DB::shouldReceive('connection')->andThrow(new RuntimeException('base injoignable'));

        $this->expectException(RuntimeException::class);

        (new VerifierAccesBaseDeDonnees)->handle(new DiagnosingHealth);
    }
}
