<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;

/**
 * Rend le point de santé /up véritablement significatif (L20) : il ne suffit pas
 * que PHP réponde, l'accès à la base doit être vérifié. Une exception levée ici
 * fait répondre /up en 500 — signal exploité par la sonde de disponibilité.
 */
class VerifierAccesBaseDeDonnees
{
    public function handle(DiagnosingHealth $event): void
    {
        // Requête minimale : échoue et propage si la base est injoignable.
        DB::connection()->select('select 1');
    }
}
