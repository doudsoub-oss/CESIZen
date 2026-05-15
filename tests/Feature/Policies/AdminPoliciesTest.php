<?php

namespace Tests\Feature\Policies;

use App\Models\AnswerOption;
use App\Models\Category;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use App\Policies\AnswerOptionPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContentPolicy;
use App\Policies\MenuItemPolicy;
use App\Policies\MenuPolicy;
use App\Policies\QuestionnairePolicy;
use App\Policies\QuestionPolicy;
use App\Policies\ResultInterpretationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the eight policies that share the same "admin can do anything,
 * super-admin can too" matrix: Category, Content, Menu, MenuItem,
 * Questionnaire, Question, AnswerOption, ResultInterpretation.
 */
class AdminPoliciesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{class-string, class-string}> */
    public static function policyModelProvider(): array
    {
        return [
            'Category' => [CategoryPolicy::class, Category::class],
            'Content' => [ContentPolicy::class, Content::class],
            'Menu' => [MenuPolicy::class, Menu::class],
            'MenuItem' => [MenuItemPolicy::class, MenuItem::class],
            'Questionnaire' => [QuestionnairePolicy::class, Questionnaire::class],
            'Question' => [QuestionPolicy::class, Question::class],
            'AnswerOption' => [AnswerOptionPolicy::class, AnswerOption::class],
            'ResultInterpretation' => [ResultInterpretationPolicy::class, ResultInterpretation::class],
        ];
    }

    #[DataProvider('policyModelProvider')]
    public function test_regular_user_is_denied_all_admin_actions(string $policyClass, string $modelClass): void
    {
        $policy = new $policyClass;
        $actor = User::factory()->create();
        $resource = $modelClass::factory()->create();

        $this->assertFalse($policy->viewAny($actor));
        $this->assertFalse($policy->view($actor, $resource));
        $this->assertFalse($policy->create($actor));
        $this->assertFalse($policy->update($actor, $resource));
        $this->assertFalse($policy->delete($actor, $resource));
    }

    #[DataProvider('policyModelProvider')]
    public function test_admin_is_allowed_all_actions(string $policyClass, string $modelClass): void
    {
        $policy = new $policyClass;
        $actor = User::factory()->admin()->create();
        $resource = $modelClass::factory()->create();

        $this->assertTrue($policy->viewAny($actor));
        $this->assertTrue($policy->view($actor, $resource));
        $this->assertTrue($policy->create($actor));
        $this->assertTrue($policy->update($actor, $resource));
        $this->assertTrue($policy->delete($actor, $resource));
    }

    #[DataProvider('policyModelProvider')]
    public function test_super_admin_is_allowed_all_actions(string $policyClass, string $modelClass): void
    {
        $policy = new $policyClass;
        $actor = User::factory()->superAdmin()->create();
        $resource = $modelClass::factory()->create();

        $this->assertTrue($policy->viewAny($actor));
        $this->assertTrue($policy->view($actor, $resource));
        $this->assertTrue($policy->create($actor));
        $this->assertTrue($policy->update($actor, $resource));
        $this->assertTrue($policy->delete($actor, $resource));
    }
}
