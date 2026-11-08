<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionPointRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionPointRule>
 */
class CompetitionPointRuleFactory extends Factory
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
            'place' => fake()->numberBetween(1, 8),
            'points' => fake()->numberBetween(1, 8),
        ];
    }
}
