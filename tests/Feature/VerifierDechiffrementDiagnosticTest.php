<?php

namespace Tests\Feature;

use App\Models\Diagnostic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerifierDechiffrementDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_succeeds_when_diagnostics_decrypt_with_current_key(): void
    {
        Diagnostic::factory()->create(['score_total' => 21]);

        $this->artisan('diagnostics:verifier-dechiffrement')
            ->assertSuccessful();
    }

    public function test_it_fails_when_an_encrypted_value_cannot_be_decrypted(): void
    {
        $diagnostic = Diagnostic::factory()->create(['score_total' => 21]);

        // Corrompt le chiffré en base (valeur illisible par la clé courante).
        DB::table('diagnostics')->where('id', $diagnostic->id)->update([
            'score_total' => 'valeur-non-dechiffrable',
        ]);

        $this->artisan('diagnostics:verifier-dechiffrement')
            ->assertFailed();
    }

    public function test_it_succeeds_on_an_empty_database(): void
    {
        $this->artisan('diagnostics:verifier-dechiffrement')
            ->expectsOutputToContain('Aucune donnée chiffrée à vérifier')
            ->assertSuccessful();
    }
}
