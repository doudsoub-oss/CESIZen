<?php

namespace Tests\Feature\Admin\Diagnostic;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireAdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_regular_user_cannot_access_questionnaires_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.questionnaires.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_questionnaire(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('admin.questionnaires.store'), [
            'title' => 'Nouveau test',
            'description' => 'desc',
            'is_active' => true,
        ])->assertRedirect(route('admin.questionnaires.index'));

        $this->assertDatabaseHas('questionnaires', [
            'title' => 'Nouveau test',
            'slug' => 'nouveau-test',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_nest_question_under_questionnaire(): void
    {
        $this->actingAsAdmin();
        $q = Questionnaire::factory()->create();

        $this->post(route('admin.questionnaires.questions.store', $q), [
            'text' => 'Une question ?',
            'is_required' => true,
            'position' => 0,
        ])->assertRedirect(route('admin.questionnaires.edit', $q));

        $this->assertDatabaseHas('questions', [
            'questionnaire_id' => $q->id,
            'text' => 'Une question ?',
        ]);
    }

    public function test_admin_can_nest_option_under_question(): void
    {
        $this->actingAsAdmin();
        $q = Questionnaire::factory()->create();
        $question = Question::factory()->create(['questionnaire_id' => $q->id]);

        $this->post(route('admin.questionnaires.questions.answer-options.store', [$q, $question]), [
            'label' => 'Jamais',
            'score' => 0,
            'position' => 0,
        ])->assertRedirect(route('admin.questionnaires.questions.edit', [$q, $question]));

        $this->assertDatabaseHas('answer_options', [
            'question_id' => $question->id,
            'label' => 'Jamais',
            'score' => 0,
        ]);
    }

    public function test_scope_bindings_reject_mismatched_question_or_option(): void
    {
        $this->actingAsAdmin();
        $q1 = Questionnaire::factory()->create();
        $q2 = Questionnaire::factory()->create();
        $question = Question::factory()->create(['questionnaire_id' => $q1->id]);
        $option = AnswerOption::factory()->create(['question_id' => $question->id]);

        // Trying to edit the question under the wrong questionnaire 404s.
        $this->get(route('admin.questionnaires.questions.edit', [$q2, $question]))
            ->assertNotFound();

        // Trying to edit the option under the wrong question 404s.
        $otherQuestion = Question::factory()->create(['questionnaire_id' => $q1->id]);
        $this->get(route('admin.questionnaires.questions.answer-options.edit', [$q1, $otherQuestion, $option]))
            ->assertNotFound();
    }

    public function test_interpretation_overlap_is_rejected_on_store(): void
    {
        $this->actingAsAdmin();
        $q = Questionnaire::factory()->create();
        ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 0,
            'max_score' => 10,
        ]);

        $this->from(route('admin.questionnaires.interpretations.create', $q))
            ->post(route('admin.questionnaires.interpretations.store', $q), [
                'min_score' => 5,
                'max_score' => 15,
                'title' => 'Overlap',
                'description' => 'x',
            ])
            ->assertSessionHasErrors('min_score');
    }

    public function test_interpretation_overlap_allows_editing_the_same_row(): void
    {
        $this->actingAsAdmin();
        $q = Questionnaire::factory()->create();
        $i = ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 0,
            'max_score' => 10,
        ]);

        $this->put(route('admin.questionnaires.interpretations.update', [$q, $i]), [
            'min_score' => 0,
            'max_score' => 12,
            'title' => $i->title,
            'description' => $i->description,
        ])->assertRedirect(route('admin.questionnaires.edit', $q));

        $this->assertSame(12, $i->fresh()->max_score);
    }

    public function test_min_greater_than_max_is_rejected(): void
    {
        $this->actingAsAdmin();
        $q = Questionnaire::factory()->create();

        $this->from(route('admin.questionnaires.interpretations.create', $q))
            ->post(route('admin.questionnaires.interpretations.store', $q), [
                'min_score' => 20,
                'max_score' => 5,
                'title' => 'Wrong',
                'description' => 'x',
            ])
            ->assertSessionHasErrors('max_score');
    }
}
