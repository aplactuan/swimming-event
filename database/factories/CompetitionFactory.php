<?php

namespace Database\Factories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $competitionDate = fake()->dateTimeBetween('+1 week', '+6 months');

        return [
            'name' => fake()->sentence(3),
            'venue' => fake()->company().' Aquatic Centre',
            'competition_date' => $competitionDate->format('Y-m-d'),
            'warm_up_time' => '07:30:00',
            'coaches_meeting_time' => '08:00:00',
            'registration_deadline' => fake()->dateTimeBetween('now', $competitionDate)->format('Y-m-d'),
            'entry_fee' => fake()->numberBetween(500, 10000),
        ];
    }
}
