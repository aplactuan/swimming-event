<?php

namespace Database\Factories;

use App\Models\Heat;
use App\Models\HeatLane;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeatLane>
 */
class HeatLaneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'heat_id' => Heat::factory(),
            'participant_id' => null,
            'lane_number' => fake()->numberBetween(1, 5),
            'finish_time_hundredths' => null,
        ];
    }

    /**
     * Indicate that the lane has a recorded finish time.
     */
    public function finished(?int $hundredths = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'finish_time_hundredths' => $hundredths ?? fake()->numberBetween(2000, 30000),
        ]);
    }
}
