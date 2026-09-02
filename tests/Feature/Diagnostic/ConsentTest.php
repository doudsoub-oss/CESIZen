<?php

namespace Tests\Feature\Diagnostic;

use App\Models\AnswerOption;
use App\Models\Diagnostic;
use App\Models\DiagnosticResponse;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consentement explicite au rattachement d'un diagnostic (L08, art. 9.2.a) et
 * retrait par suppression unitaire.
 */
class ConsentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Questionnaire, 1: Question, 2: AnswerOption}
     */
    private function seedScorable(): array
    {
        $questionnaire = Questionnaire::factory()->create(['is_active' => true]);
        $question = Question::factory()->create(['questionnaire_id' => $questionnaire->id]);
        $option = AnswerOption::factory()->create(['question_id' => $question->id, 'score' => 3]);

        return [$questionnaire, $question, $option];
    }

    public function test_authenticated_submission_without_consent_is_not_persisted(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostic.submit', $questionnaire), [
                'answers' => [$question->id => $option->id],
                // consentement absent → « ne pas conserver »
            ])
            ->assertOk();

        $this->assertDatabaseCount('diagnostics', 0);
    }

    public function test_explicit_refusal_is_not_persisted_even_if_forged(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostic.submit', $questionnaire), [
                'answers' => [$question->id => $option->id],
                'consent' => false,
            ])
            ->assertOk();

        $this->assertDatabaseCount('diagnostics', 0);
    }

    public function test_consent_persists_the_diagnostic_and_records_consented_at(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostic.submit', $questionnaire), [
                'answers' => [$question->id => $option->id],
                'consent' => true,
            ])
            ->assertOk();

        $diagnostic = Diagnostic::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($diagnostic->consented_at);
    }

    public function test_unit_deletion_removes_the_diagnostic_and_its_responses(): void
    {
        [$questionnaire, $question, $option] = $this->seedScorable();
        $user = User::factory()->create();

        $diagnostic = Diagnostic::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);
        DiagnosticResponse::factory()->create([
            'diagnostic_id' => $diagnostic->id,
            'question_id' => $question->id,
            'answer_option_id' => $option->id,
            'score' => 3,
        ]);

        $this->actingAs($user)
            ->delete(route('diagnostic.history.destroy', $diagnostic))
            ->assertRedirect(route('diagnostic.history'));

        $this->assertDatabaseMissing('diagnostics', ['id' => $diagnostic->id]);
        $this->assertDatabaseCount('diagnostic_responses', 0);
    }

    public function test_cannot_delete_another_users_diagnostic(): void
    {
        [$questionnaire] = $this->seedScorable();
        $me = User::factory()->create();
        $someoneElse = User::factory()->create();

        $theirs = Diagnostic::factory()->create([
            'user_id' => $someoneElse->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $this->actingAs($me)
            ->delete(route('diagnostic.history.destroy', $theirs))
            ->assertForbidden();

        $this->assertDatabaseHas('diagnostics', ['id' => $theirs->id]);
    }

    public function test_privacy_policy_page_is_public(): void
    {
        $this->get(route('privacy-policy'))->assertOk();
    }
}
