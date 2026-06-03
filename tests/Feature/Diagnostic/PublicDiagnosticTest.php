<?php

namespace Tests\Feature\Diagnostic;

use App\Models\AnswerOption;
use App\Models\Diagnostic;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PublicDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private function seedQuestionnaire(): array
    {
        $q = Questionnaire::factory()->create(['is_active' => true]);
        $question = Question::factory()->create(['questionnaire_id' => $q->id, 'is_required' => true]);
        $low = AnswerOption::factory()->create(['question_id' => $question->id, 'score' => 0]);
        $high = AnswerOption::factory()->create(['question_id' => $question->id, 'score' => 5]);
        ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 0,
            'max_score' => 2,
            'title' => 'Low',
        ]);
        ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 3,
            'max_score' => 10,
            'title' => 'High',
        ]);

        return [$q, $question, $low, $high];
    }

    public function test_index_redirects_when_only_one_active_questionnaire(): void
    {
        [$q] = $this->seedQuestionnaire();

        $this->get(route('diagnostic.index'))
            ->assertRedirect(route('diagnostic.show', $q));
    }

    public function test_show_works_for_active_questionnaire(): void
    {
        [$q] = $this->seedQuestionnaire();

        $this->get(route('diagnostic.show', $q))->assertOk();
    }

    public function test_show_returns_404_for_inactive_questionnaire(): void
    {
        $q = Questionnaire::factory()->create(['is_active' => false]);

        $this->get(route('diagnostic.show', $q))->assertNotFound();
    }

    /**
     * The run page must expose each question's options under the snake_case
     * `answer_options` key (Eloquent serializes the `answerOptions` relation
     * that way); the Vue component reads exactly that key to render the radios.
     */
    public function test_show_exposes_answer_options_under_snake_case_key(): void
    {
        [$q] = $this->seedQuestionnaire();

        $this->get(route('diagnostic.show', $q))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('public/Diagnostic/Run')
                ->has('questionnaire.questions.0.answer_options', 2, fn (AssertableInertia $option) => $option
                    ->has('id')
                    ->has('label')
                    ->has('score')
                    ->etc()
                )
            );
    }

    public function test_anonymous_submission_returns_result_without_db_write(): void
    {
        [$q, $question, , $high] = $this->seedQuestionnaire();

        $this->post(route('diagnostic.submit', $q), [
            'answers' => [$question->id => $high->id],
        ])->assertOk();

        $this->assertDatabaseCount('diagnostics', 0);
        $this->assertDatabaseCount('diagnostic_responses', 0);
    }

    public function test_authenticated_submission_persists_diagnostic_and_responses(): void
    {
        [$q, $question, , $high] = $this->seedQuestionnaire();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostic.submit', $q), [
                'answers' => [$question->id => $high->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('diagnostics', [
            'user_id' => $user->id,
            'questionnaire_id' => $q->id,
            'score_total' => 5,
        ]);
        $this->assertDatabaseHas('diagnostic_responses', [
            'question_id' => $question->id,
            'answer_option_id' => $high->id,
            'score' => 5,
        ]);
    }

    public function test_missing_required_answer_is_rejected(): void
    {
        [$q] = $this->seedQuestionnaire();

        $this->from(route('diagnostic.show', $q))
            ->post(route('diagnostic.submit', $q), ['answers' => []])
            ->assertSessionHasErrors();
    }

    public function test_option_from_another_question_is_rejected(): void
    {
        [$q, $question, $low, $high] = $this->seedQuestionnaire();
        $otherQuestion = Question::factory()->create(['questionnaire_id' => $q->id]);
        $otherOption = AnswerOption::factory()->create(['question_id' => $otherQuestion->id, 'score' => 1]);

        $this->from(route('diagnostic.show', $q))
            ->post(route('diagnostic.submit', $q), [
                'answers' => [$question->id => $otherOption->id],
            ])
            ->assertSessionHasErrors("answers.{$question->id}");
    }

    public function test_history_index_requires_auth(): void
    {
        $this->get(route('diagnostic.history'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_their_own_history(): void
    {
        [$q] = $this->seedQuestionnaire();
        $me = User::factory()->create();
        $someoneElse = User::factory()->create();

        $mine = Diagnostic::factory()->create(['user_id' => $me->id, 'questionnaire_id' => $q->id]);
        $theirs = Diagnostic::factory()->create(['user_id' => $someoneElse->id, 'questionnaire_id' => $q->id]);

        $this->actingAs($me)->get(route('diagnostic.history'))->assertOk();

        // Other user's diagnostic detail must be inaccessible.
        $this->actingAs($me)
            ->get(route('diagnostic.history.show', $theirs))
            ->assertForbidden();

        $this->actingAs($me)
            ->get(route('diagnostic.history.show', $mine))
            ->assertOk();
    }
}
