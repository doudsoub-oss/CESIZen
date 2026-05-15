<?php

namespace Database\Factories;

use App\Models\Diagnostic;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diagnostic>
 */
class DiagnosticFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'questionnaire_id' => Questionnaire::factory(),
            'score_total' => fake()->numberBetween(0, 30),
            'result_interpretation_id' => null,
            'completed_at' => now(),
        ];
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
            'score_total' => 0,
        ]);
    }
}
