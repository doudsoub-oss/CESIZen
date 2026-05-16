<?php

namespace Tests\Feature\Diagnostic;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Services\DiagnosticScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DiagnosticScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private function buildQuestionnaire(): Questionnaire
    {
        $q = Questionnaire::factory()->create(['is_active' => true]);

        $q1 = Question::factory()->create(['questionnaire_id' => $q->id, 'position' => 0]);
        $q2 = Question::factory()->create(['questionnaire_id' => $q->id, 'position' => 1]);

        AnswerOption::factory()->create(['question_id' => $q1->id, 'score' => 0, 'position' => 0]);
        AnswerOption::factory()->create(['question_id' => $q1->id, 'score' => 5, 'position' => 1]);
        AnswerOption::factory()->create(['question_id' => $q2->id, 'score' => 0, 'position' => 0]);
        AnswerOption::factory()->create(['question_id' => $q2->id, 'score' => 10, 'position' => 1]);

        return $q->refresh()->load('questions.answerOptions');
    }

    public function test_sums_scores_across_answers(): void
    {
        $q = $this->buildQuestionnaire();
        $service = new DiagnosticScoringService;

        [$q1, $q2] = $q->questions;
        $o1 = $q1->answerOptions->firstWhere('score', 5);
        $o2 = $q2->answerOptions->firstWhere('score', 10);

        $result = $service->score($q, [
            $q1->id => $o1->id,
            $q2->id => $o2->id,
        ]);

        $this->assertSame(15, $result['total']);
        $this->assertCount(2, $result['responses']);
    }

    public function test_finds_matching_interpretation(): void
    {
        $q = $this->buildQuestionnaire();
        ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 0,
            'max_score' => 9,
            'title' => 'Low',
        ]);
        $high = ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 10,
            'max_score' => 20,
            'title' => 'High',
        ]);

        $service = new DiagnosticScoringService;
        [$q1, $q2] = $q->questions;
        $o1 = $q1->answerOptions->firstWhere('score', 5);
        $o2 = $q2->answerOptions->firstWhere('score', 10);

        $result = $service->score($q, [$q1->id => $o1->id, $q2->id => $o2->id]);

        $this->assertSame($high->id, $result['interpretation']->id);
    }

    public function test_boundary_scores_match_inclusive_ranges(): void
    {
        $q = $this->buildQuestionnaire();
        ResultInterpretation::factory()->create([
            'questionnaire_id' => $q->id,
            'min_score' => 0,
            'max_score' => 10,
        ]);

        $service = new DiagnosticScoringService;

        $this->assertNotNull($service->interpretationForScore($q, 0));
        $this->assertNotNull($service->interpretationForScore($q, 10));
        $this->assertNull($service->interpretationForScore($q, 11));
    }

    public function test_rejects_option_from_a_different_question(): void
    {
        $q = $this->buildQuestionnaire();
        [$q1, $q2] = $q->questions;
        $optionFromOtherQuestion = $q2->answerOptions->first();

        $this->expectException(InvalidArgumentException::class);
        (new DiagnosticScoringService)->score($q, [$q1->id => $optionFromOtherQuestion->id]);
    }

    public function test_rejects_option_from_a_different_questionnaire(): void
    {
        $q = $this->buildQuestionnaire();
        $other = $this->buildQuestionnaire();
        [$myQ1] = $q->questions;
        $foreignOption = $other->questions->first()->answerOptions->first();

        $this->expectException(InvalidArgumentException::class);
        (new DiagnosticScoringService)->score($q, [$myQ1->id => $foreignOption->id]);
    }
}
