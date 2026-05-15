<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Category;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use Illuminate\Database\Seeder;

class CESIZenSeeder extends Seeder
{
    public function run(): void
    {
        // Create users
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@cesizen.fr',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@cesizen.fr',
        ]);

        User::factory()->create([
            'name' => 'Utilisateur Test',
            'email' => 'user@cesizen.fr',
        ]);

        // Create top-level categories
        $stressCategory = Category::create([
            'name' => 'Comprendre le stress',
            'slug' => 'comprendre-le-stress',
            'description' => 'Articles pour comprendre les mécanismes du stress',
            'position' => 0,
        ]);

        $relaxationCategory = Category::create([
            'name' => 'Techniques de relaxation',
            'slug' => 'techniques-relaxation',
            'description' => 'Méthodes et exercices pour se relaxer',
            'position' => 1,
        ]);

        Category::create([
            'name' => 'Santé mentale',
            'slug' => 'sante-mentale',
            'description' => 'Informations sur la santé mentale',
            'position' => 2,
        ]);

        Category::create([
            'name' => 'Ressources',
            'slug' => 'ressources',
            'description' => 'Liens et ressources utiles',
            'position' => 3,
        ]);

        // Nested sub-category under "Techniques de relaxation"
        $breathingCategory = Category::create([
            'name' => 'Respiration profonde',
            'slug' => 'respiration-profonde',
            'description' => 'Exercices de respiration pour réduire le stress',
            'parent_id' => $relaxationCategory->id,
            'position' => 0,
        ]);

        // Sample contents
        Content::create([
            'category_id' => $stressCategory->id,
            'title' => 'Qu\'est-ce que le stress ?',
            'slug' => 'quest-ce-que-le-stress',
            'excerpt' => 'Le stress est une réaction naturelle de l\'organisme face à une situation perçue comme menaçante.',
            'body' => "Le stress est une réponse physiologique et psychologique de l'organisme face à des situations perçues comme menaçantes ou exigeantes. Cette réaction, également appelée « réponse de lutte ou de fuite », prépare le corps à faire face à un danger potentiel.\n\n## Les différents types de stress\n\nIl existe deux types principaux de stress :\n\n1. **Le stress aigu** : de courte durée, il survient en réponse à une situation immédiate.\n2. **Le stress chronique** : prolongé dans le temps, il peut avoir des effets néfastes sur la santé.\n\n## Les symptômes du stress\n\nLe stress peut se manifester de différentes manières :\n- Tensions musculaires\n- Troubles du sommeil\n- Irritabilité\n- Difficultés de concentration\n- Fatigue",
            'type' => 'article',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        Content::create([
            'category_id' => $breathingCategory->id,
            'title' => 'La respiration carrée',
            'slug' => 'respiration-carree',
            'excerpt' => 'Une technique de respiration simple pour calmer rapidement le système nerveux.',
            'body' => "La respiration carrée, aussi appelée « box breathing », est une technique utilisée pour réduire le stress et améliorer la concentration.\n\n## La méthode\n\n1. Inspirez par le nez pendant 4 secondes.\n2. Retenez votre souffle pendant 4 secondes.\n3. Expirez par la bouche pendant 4 secondes.\n4. Retenez votre souffle, poumons vides, pendant 4 secondes.\n\nRépétez le cycle 4 à 8 fois.\n\n## Quand l'utiliser\n\n- Avant une situation stressante (réunion, examen)\n- Pour s'endormir plus facilement\n- Pendant une crise d'anxiété",
            'type' => 'resource',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        Content::create([
            'category_id' => null,
            'title' => 'Mentions légales',
            'slug' => 'mentions-legales',
            'excerpt' => 'Informations légales et politique de confidentialité.',
            'body' => "## Éditeur\n\nCESIZen est une application développée dans le cadre d'un projet pédagogique.\n\n## Protection des données personnelles\n\nConformément au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles.\n\n## Cookies\n\nL'application utilise uniquement les cookies strictement nécessaires à son fonctionnement (session, CSRF).",
            'type' => 'page',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        // Create main menu
        $mainMenu = Menu::create([
            'name' => 'Menu principal',
            'location' => 'main',
        ]);

        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Accueil', 'url' => '/', 'position' => 0]);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Informations', 'url' => '/informations', 'position' => 1]);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Diagnostic', 'url' => '/diagnostic', 'position' => 2]);

        // Create stress questionnaire (PSS-10 inspired)
        $questionnaire = Questionnaire::create([
            'title' => 'Échelle de stress perçu',
            'slug' => 'echelle-stress-percu',
            'description' => 'Ce questionnaire permet d\'évaluer votre niveau de stress perçu au cours du dernier mois.',
            'instructions' => 'Pour chaque question, indiquez à quelle fréquence vous vous êtes senti(e) ainsi au cours du dernier mois. Répondez spontanément, il n\'y a pas de bonne ou mauvaise réponse.',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        // Create questions with answer options
        $questions = [
            'Au cours du dernier mois, avez-vous été dérangé(e) par un événement inattendu ?',
            'Au cours du dernier mois, avez-vous eu le sentiment de ne pas pouvoir contrôler les choses importantes de votre vie ?',
            'Au cours du dernier mois, vous êtes-vous senti(e) nerveux(se) ou stressé(e) ?',
            'Au cours du dernier mois, avez-vous eu confiance en votre capacité à gérer vos problèmes personnels ?',
            'Au cours du dernier mois, avez-vous senti que les choses allaient comme vous le vouliez ?',
            'Au cours du dernier mois, avez-vous pensé que vous ne pouviez pas faire face à tout ce que vous aviez à faire ?',
            'Au cours du dernier mois, avez-vous été capable de maîtriser votre énervement ?',
            'Au cours du dernier mois, avez-vous senti que vous dominiez la situation ?',
            'Au cours du dernier mois, vous êtes-vous senti(e) irrité(e) parce que les événements échappaient à votre contrôle ?',
            'Au cours du dernier mois, avez-vous trouvé que les difficultés s\'accumulaient à un tel point que vous ne pouviez les contrôler ?',
        ];

        $answerLabels = ['Jamais', 'Presque jamais', 'Parfois', 'Assez souvent', 'Très souvent'];

        foreach ($questions as $index => $questionText) {
            $question = Question::create([
                'questionnaire_id' => $questionnaire->id,
                'text' => $questionText,
                'position' => $index,
                'is_required' => true,
            ]);

            // Questions 4, 5, 7, 8 are reverse-scored
            $isReversed = in_array($index, [3, 4, 6, 7]);

            foreach ($answerLabels as $answerIndex => $label) {
                $score = $isReversed ? (4 - $answerIndex) : $answerIndex;
                AnswerOption::create([
                    'question_id' => $question->id,
                    'label' => $label,
                    'score' => $score,
                    'position' => $answerIndex,
                ]);
            }
        }

        // Create result interpretations
        ResultInterpretation::create([
            'questionnaire_id' => $questionnaire->id,
            'min_score' => 0,
            'max_score' => 13,
            'title' => 'Niveau de stress faible',
            'description' => 'Votre niveau de stress est faible. Vous semblez bien gérer les situations stressantes de la vie quotidienne.',
            'recommendations' => 'Continuez à maintenir vos bonnes habitudes et prenez soin de vous. La pratique régulière d\'activités relaxantes peut vous aider à maintenir ce niveau.',
            'color' => 'green',
        ]);

        ResultInterpretation::create([
            'questionnaire_id' => $questionnaire->id,
            'min_score' => 14,
            'max_score' => 26,
            'title' => 'Niveau de stress modéré',
            'description' => 'Votre niveau de stress est modéré. Vous ressentez un certain stress mais il reste gérable.',
            'recommendations' => 'Pensez à intégrer des techniques de relaxation dans votre quotidien : respiration profonde, méditation, activité physique. Identifiez les sources de stress et cherchez des solutions.',
            'color' => 'yellow',
        ]);

        ResultInterpretation::create([
            'questionnaire_id' => $questionnaire->id,
            'min_score' => 27,
            'max_score' => 40,
            'title' => 'Niveau de stress élevé',
            'description' => 'Votre niveau de stress est élevé. Il est important de prendre des mesures pour le réduire.',
            'recommendations' => 'Nous vous conseillons de consulter un professionnel de santé. En attendant, essayez de vous accorder des moments de détente, de pratiquer des exercices de respiration et d\'identifier les principales sources de stress pour y remédier.',
            'color' => 'red',
        ]);
    }
}
