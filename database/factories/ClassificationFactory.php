<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classification>
 */
class ClassificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'parent_id' => null,
            'name' => fake()->randomElement(['Novice', 'Developmental', 'Class A', 'Class B']),
            'sort_order' => 1,
        ];
    }

    /**
     * Indicate that the classification is a child of the given parent.
     */
    public function childOf(Classification $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'competition_id' => $parent->competition_id,
            'parent_id' => $parent->id,
        ]);
    }
}
