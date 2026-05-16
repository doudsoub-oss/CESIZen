<?php

namespace Database\Factories;

use App\Enums\ContentType;
use App\Models\Category;
use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'body' => fake()->paragraphs(5, true),
            'type' => fake()->randomElement(ContentType::cases()),
            'is_published' => true,
            'published_at' => now(),
            'created_by' => User::factory(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function article(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContentType::Article,
        ]);
    }

    public function page(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContentType::Page,
            'category_id' => null,
        ]);
    }
}
