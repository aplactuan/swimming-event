<?php

namespace Database\Factories;

use App\Enums\EventGender;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
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
            'name' => fake()->randomElement([
                '25m Freestyle',
                '25m Breaststroke',
                '50m Freestyle',
                '50m Backstroke',
            ]),
            'gender' => fake()->randomElement(EventGender::cases()),
            'sort_order' => 1,
        ];
    }
}
