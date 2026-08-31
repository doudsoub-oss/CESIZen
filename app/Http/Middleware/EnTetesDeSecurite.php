<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique les dispositifs de transport et d'en-têtes du Tableau 10 (traite
 * R5/R12) : redirection HTTPS hors développement, en-tête de transport strict
 * (HSTS) quand la connexion est chiffrée, et en-têtes de durcissement
 * (type-options, cadrage, référent, permissions, politique de contenu).
 */
class EnTetesDeSecurite
{
    /**
     * Environnements déployés où le transport chiffré est imposé.
     *
     * @var list<string>
     */
    private const ENVIRONNEMENTS_HTTPS = ['production', 'staging', 'recette'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->doitForcerHttps() && ! $request->isSecure()) {
            return redirect()->to(secure_url($request->getRequestUri()), 301);
        }

        /** @var Response $response */
        $response = $next($request);

        $this->appliquerEnTetes($request, $response);

        return $response;
    }

    private function doitForcerHttps(): bool
    {
        return app()->environment(self::ENVIRONNEMENTS_HTTPS);
    }

    private function appliquerEnTetes(Request $request, Response $response): void
    {
        $headers = $response->headers;

        // Transport strict : uniquement sur une connexion déjà chiffrée, jamais
        // en développement local en clair.
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        $headers->set($this->nomEnTeteCsp(), $this->politiqueDeContenu());

        // La recette ne doit pas être indexée par les moteurs (L06, traite R10).
        if (app()->environment('recette')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    private function nomEnTeteCsp(): string
    {
        return config('security.csp_report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';
    }

    /**
     * Le nonce est partagé avec Vite (App\Providers\AppServiceProvider appelle
     * Vite::useCspNonce()), de sorte que les balises de script générées le
     * portent et restent autorisées par script-src.
     *
     * style-src conserve 'unsafe-inline' tant que Vue injecte des styles de
     * transition en ligne : la CSP démarre en mode rapport et sera durcie par
     * paliers (voir docs/securite/en-tetes-securite.md).
     */
    private function politiqueDeContenu(): string
    {
        $nonce = Vite::cspNonce();

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }
}
