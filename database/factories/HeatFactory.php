<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Heat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Heat>
 */
class HeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'heat_number' => 1,
        ];
    }

    /**
     * Indicate that the heat has the given number of empty lanes.
     */
    public function withLanes(int $count = 5): static
    {
        return $this->afterCreating(function (Heat $heat) use ($count): void {
            foreach (range(1, $count) as $laneNumber) {
                $heat->lanes()->create(['lane_number' => $laneNumber]);
            }
        });
    }
}
