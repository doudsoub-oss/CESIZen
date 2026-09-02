<?php

namespace Tests\Feature\Securite;

use App\Models\AnswerOption;
use App\Models\Diagnostic;
use App\Models\DiagnosticResponse;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chiffrement applicatif au repos des résultats de diagnostic (L05b, traite R1).
 */
class ChiffrementDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Questionnaire, 1: Question, 2: AnswerOption}
     */
    private function seedScorable(): array
    {
        $questionnaire = Questionnaire::factory()->create(['is_active' => true]);
        $question = Question::factory()->create(['questionnaire_id' => $questionnaire->id]);
        $option = AnswerOption::factory()->create(['question_id' => $question->id, 'score' => 5]);

        return [$questionnaire, $question, $option];
    }

    public function test_score_is_not_stored_in_clear_and_is_restored_by_the_model(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostic.submit', $questionnaire), [
                'answers' => [$question->id => $option->id],
                'consent' => true,
            ])
            ->assertOk();

        // Inspection SQL directe : aucune valeur en clair.
        $raw = DB::table('diagnostics')->first();
        $this->assertNotSame('5', (string) $raw->score_total);
        $this->assertStringNotContainsString('5', substr((string) $raw->score_total, 0, 3));

        // Le modèle rechargé restitue exactement la valeur d'origine.
        $diagnostic = Diagnostic::firstOrFail();
        $this->assertSame(5, (int) $diagnostic->score_total);
    }

    public function test_answer_and_response_score_are_encrypted_but_still_readable_by_the_app(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostic.submit', $questionnaire), [
                'answers' => [$question->id => $option->id],
                'consent' => true,
            ])
            ->assertOk();

        $raw = DB::table('diagnostic_responses')->first();

        // La réponse choisie n'est plus une FK en clair : la jointure SQL qui
        // reconstruirait le score (scénario R1) est impossible.
        $this->assertNotEquals((string) $option->id, (string) $raw->answer_option_id);
        $this->assertNotEquals('5', (string) $raw->score);
        $this->assertSame(
            0,
            DB::table('diagnostic_responses')->where('answer_option_id', $option->id)->count(),
            'La réponse ne doit pas être retrouvable par sa clé étrangère en clair.'
        );

        // L'application, elle, restitue tout via Eloquent.
        $response = DiagnosticResponse::firstOrFail();
        $this->assertSame($option->id, (int) $response->answer_option_id);
        $this->assertSame(5, (int) $response->score);
        $this->assertSame('5', (string) $response->answerOption->score);
    }

    public function test_encryption_command_is_idempotent(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        // Insertion EN CLAIR via le Query Builder (contourne les casts), pour
        // simuler des données antérieures au chiffrement.
        $diagnosticId = DB::table('diagnostics')->insertGetId([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'score_total' => '5',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('diagnostic_responses')->insert([
            'diagnostic_id' => $diagnosticId,
            'question_id' => $question->id,
            'answer_option_id' => (string) $option->id,
            'score' => '5',
            'created_at' => now(),
        ]);

        // Premier passage : chiffre.
        $this->artisan('diagnostics:chiffrer')->assertSuccessful();

        $rawAfterFirst = DB::table('diagnostics')->where('id', $diagnosticId)->value('score_total');
        $this->assertNotSame('5', (string) $rawAfterFirst);
        $this->assertSame(5, (int) Diagnostic::findOrFail($diagnosticId)->score_total);

        // Second passage : idempotent, ne rechiffre pas.
        $this->artisan('diagnostics:chiffrer')->assertSuccessful();

        $rawAfterSecond = DB::table('diagnostics')->where('id', $diagnosticId)->value('score_total');
        $this->assertSame($rawAfterFirst, $rawAfterSecond, 'La valeur chiffrée ne doit pas changer au second passage.');
        $this->assertSame(5, (int) Diagnostic::findOrFail($diagnosticId)->score_total);
    }

    public function test_dry_run_reports_without_modifying(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $diagnosticId = DB::table('diagnostics')->insertGetId([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'score_total' => '5',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('diagnostics:chiffrer', ['--dry-run' => true])->assertSuccessful();

        // Rien n'a été modifié : la valeur est toujours en clair.
        $this->assertSame('5', (string) DB::table('diagnostics')->where('id', $diagnosticId)->value('score_total'));
    }

    public function test_re_encryption_restores_readability_after_key_rotation(): void
    {
        [$questionnaire] = $this->seedScorable();
        $user = User::factory()->create();

        // Données chiffrées avec une ANCIENNE clé applicative.
        $cipher = config('app.cipher');
        $oldKey = Encrypter::generateKey($cipher);
        $old = new Encrypter($oldKey, $cipher);

        $id = DB::table('diagnostics')->insertGetId([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'score_total' => $old->encryptString('5'),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rechiffrement avec la clé courante en fournissant l'ancienne.
        $this->artisan('diagnostics:rechiffrer', [
            '--ancienne-cle' => 'base64:'.base64_encode($oldKey),
        ])->assertSuccessful();

        // Le modèle (clé courante) lit désormais la valeur.
        $this->assertSame(5, (int) Diagnostic::findOrFail($id)->score_total);
    }

    public function test_anonymous_diagnostic_is_still_not_persisted(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();

        $this->post(route('diagnostic.submit', $questionnaire), [
            'answers' => [$question->id => $option->id],
        ])->assertOk();

        $this->assertDatabaseCount('diagnostics', 0);
    }
}
