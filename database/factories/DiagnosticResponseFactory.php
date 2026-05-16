<?php

namespace Database\Factories;

use App\Models\AnswerOption;
use App\Models\Diagnostic;
use App\Models\DiagnosticResponse;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosticResponse>
 */
class DiagnosticResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'diagnostic_id' => Diagnostic::factory(),
            'question_id' => Question::factory(),
            'answer_option_id' => AnswerOption::factory(),
            'score' => fake()->numberBetween(0, 4),
        ];
    }
}
