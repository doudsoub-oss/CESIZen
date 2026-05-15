<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'location' => fake()->randomElement(['main', 'footer', 'sidebar']),
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'main',
        ]);
    }

    public function footer(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'footer',
        ]);
    }
}
