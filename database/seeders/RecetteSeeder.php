<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Category;
use App\Models\Content;
use App\Models\Diagnostic;
use App\Models\DiagnosticResponse;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Jeu de données FICTIF pour la recette (traite R10). Toutes les personnes sont
 * générées par Faker avec des adresses non routables (safeEmail) : aucune donnée
 * réelle, aucune adresse susceptible d'exister. Ne doit jamais tourner en
 * production.
 */
class RecetteSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Le RecetteSeeder ne doit jamais être exécuté en production : '.
                'il insère des données fictives.'
            );
        }

        $admin = User::factory()->admin()->create(['name' => 'Adminatrice de recette']);
        User::factory()->superAdmin()->create(['name' => 'Super-administrateur de recette']);
        $users = User::factory()->count(8)->create();

        $this->seedEditorialContent($admin);
        $questionnaire = $this->seedQuestionnaire($admin);
        $this->seedDiagnostics($users, $questionnaire);
    }

    private function seedEditorialContent(User $admin): void
    {
        Category::factory()
            ->count(3)
            ->create()
            ->each(function (Category $category) use ($admin): void {
                Content::factory()->count(2)->create([
                    'category_id' => $category->id,
                    'created_by' => $admin->id,
                ]);
            });
    }

    /**
     * Questionnaire complet et scorable (questions, options 0..4, tranches).
     */
    private function seedQuestionnaire(User $admin): Questionnaire
    {
        $questionnaire = Questionnaire::factory()->create([
            'title' => 'Questionnaire de recette',
            'created_by' => $admin->id,
        ]);

        ResultInterpretation::factory()->low()->create(['questionnaire_id' => $questionnaire->id]);
        ResultInterpretation::factory()->medium()->create(['questionnaire_id' => $questionnaire->id]);
        ResultInterpretation::factory()->high()->create(['questionnaire_id' => $questionnaire->id]);

        $labels = ['Jamais', 'Presque jamais', 'Parfois', 'Assez souvent', 'Très souvent'];

        for ($position = 0; $position < 6; $position++) {
            $question = Question::factory()->create([
                'questionnaire_id' => $questionnaire->id,
                'position' => $position,
            ]);

            foreach ($labels as $score => $label) {
                AnswerOption::factory()->create([
                    'question_id' => $question->id,
                    'label' => $label,
                    'score' => $score,
                    'position' => $score,
                ]);
            }
        }

        return $questionnaire;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedDiagnostics($users, Questionnaire $questionnaire): void
    {
        $questions = $questionnaire->questions()->with('answerOptions')->get();
        $interpretations = $questionnaire->interpretations()->get();

        foreach ($users->take(5) as $user) {
            $chosen = $questions->map(fn (Question $q) => [
                'question' => $q,
                'option' => $q->answerOptions->random(),
            ]);

            $total = $chosen->sum(fn (array $row) => $row['option']->score);

            $interpretation = $interpretations->first(
                fn (ResultInterpretation $i) => $total >= $i->min_score && $total <= $i->max_score
            );

            $diagnostic = Diagnostic::factory()->create([
                'user_id' => $user->id,
                'questionnaire_id' => $questionnaire->id,
                'score_total' => $total,
                'result_interpretation_id' => $interpretation?->id,
            ]);

            foreach ($chosen as $row) {
                DiagnosticResponse::factory()->create([
                    'diagnostic_id' => $diagnostic->id,
                    'question_id' => $row['question']->id,
                    'answer_option_id' => $row['option']->id,
                    'score' => $row['option']->score,
                ]);
            }
        }
    }
}
