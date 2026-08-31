<?php

namespace Tests\Feature\Securite;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre le durcissement du transport et des en-têtes (lot L04, traite R5/R12) :
 * en-têtes de sécurité, HSTS conditionné au HTTPS, CSP en mode rapport par
 * défaut, cookie de session sécurisé, confiance des mandataires et redirection
 * HTTPS hors développement.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
    }

    public function test_hsts_is_absent_on_plain_http(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_present_over_https_behind_a_trusted_proxy(): void
    {
        // X-Forwarded-Proto n'est honoré que si la confiance des mandataires est
        // configurée : ce test valide donc aussi trustProxies.
        $response = $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REMOTE_ADDR' => '10.0.0.5',
        ])->get(route('login'));

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_csp_is_report_only_by_default(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('Content-Security-Policy-Report-Only');
        $response->assertHeaderMissing('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString('nonce-', $csp);
    }

    public function test_csp_becomes_enforcing_when_configured(): void
    {
        config(['security.csp_report_only' => false]);

        $response = $this->get(route('login'));

        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_session_cookie_is_secure_httponly_and_samesite(): void
    {
        config(['session.secure' => true]);

        $response = $this->get(route('login'));

        $name = config('session.cookie');
        $cookie = collect($response->headers->getCookies())
            ->firstWhere(fn ($c) => $c->getName() === $name);

        $this->assertNotNull($cookie, 'Le cookie de session doit être présent.');
        $this->assertTrue($cookie->isSecure(), 'Le cookie doit porter Secure.');
        $this->assertTrue($cookie->isHttpOnly(), 'Le cookie doit porter HttpOnly.');
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_http_is_redirected_to_https_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get(route('login'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));

        $this->app['env'] = 'testing';
    }

    public function test_no_https_redirect_in_testing_environment(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }
}
