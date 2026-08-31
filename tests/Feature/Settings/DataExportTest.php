<?php

namespace Tests\Feature\Settings;

use App\Models\AnswerOption;
use App\Models\Diagnostic;
use App\Models\DiagnosticResponse;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Droit à la portabilité — export des données (L07, traite R11 et R4).
 */
class DataExportTest extends TestCase
{
    use RefreshDatabase;

    private function seedDiagnosticFor(User $user): void
    {
        $questionnaire = Questionnaire::factory()->create(['title' => 'Échelle de stress']);
        $question = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'text' => 'Question A ?']);
        $option = AnswerOption::factory()->create(['question_id' => $question->id, 'label' => 'Souvent', 'score' => 5]);
        $interpretation = ResultInterpretation::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'min_score' => 0,
            'max_score' => 10,
            'title' => 'Niveau modéré',
        ]);

        $diagnostic = Diagnostic::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'score_total' => 5,
            'result_interpretation_id' => $interpretation->id,
        ]);
        DiagnosticResponse::factory()->create([
            'diagnostic_id' => $diagnostic->id,
            'question_id' => $question->id,
            'answer_option_id' => $option->id,
            'score' => 5,
        ]);
    }

    public function test_unauthenticated_export_is_refused(): void
    {
        $this->get(route('profile.data-export'))->assertRedirect(route('login'));
    }

    public function test_export_returns_a_json_download_with_a_timestamped_filename(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.data-export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('.json', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_user_exports_only_their_own_data(): void
    {
        $me = User::factory()->create(['email' => 'moi@example.org']);
        $this->seedDiagnosticFor($me);

        $someoneElse = User::factory()->create(['email' => 'autre@example.org']);
        $this->seedDiagnosticFor($someoneElse);

        $response = $this->actingAs($me)->get(route('profile.data-export'));
        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame('moi@example.org', $payload['compte']['email']);
        $this->assertCount(1, $payload['diagnostics']);
        $this->assertSame(5, $payload['diagnostics'][0]['score']);
        $this->assertSame('Niveau modéré', $payload['diagnostics'][0]['interpretation']);
        $this->assertSame('Souvent', $payload['diagnostics'][0]['reponses'][0]['reponse']);

        // Rien de l'autre personne n'apparaît.
        $this->assertStringNotContainsString('autre@example.org', $response->streamedContent());
    }

    public function test_export_contains_no_secret_or_password_hash(): void
    {
        $user = User::factory()->withTwoFactor()->create();
        $hash = $user->getAuthPassword();

        $content = $this->actingAs($user)->get(route('profile.data-export'))->streamedContent();

        $this->assertStringNotContainsString($hash, $content);
        $this->assertStringNotContainsString('two_factor', $content);
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('remember_token', $content);
        $this->assertStringNotContainsString((string) $user->two_factor_secret, $content);
    }

    public function test_export_is_recorded_in_the_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.data-export'))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'user.data_exported',
        ]);
    }

    public function test_export_is_rate_limited_to_three_per_day(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($user)->get(route('profile.data-export'))->assertOk();
        }

        $this->actingAs($user)->get(route('profile.data-export'))->assertTooManyRequests();
    }
}
