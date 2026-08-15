<?php

namespace Database\Factories;

use App\Enums\ParticipantGender;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
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
            'classification_id' => fn (array $attributes) => Classification::factory()->create([
                'competition_id' => $attributes['competition_id'],
            ]),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(ParticipantGender::cases()),
            'team' => fake()->company().' Swim Club',
            'birthdate' => fake()->dateTimeBetween('-16 years', '-6 years')->format('Y-m-d'),
            'paid' => false,
        ];
    }

    /**
     * Indicate that the participant has paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'paid' => true,
        ]);
    }
}
