<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Les tests ne dépendent jamais des ressources compilées par Vite : le rendu
     * de la page racine (@vite dans app.blade.php) ne doit pas exiger le
     * manifest, absent tant qu'on n'a pas lancé `npm run build`. La chaîne
     * d'intégration teste sans construire le front (étapes distinctes).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
