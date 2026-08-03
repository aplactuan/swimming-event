<?php

namespace Database\Factories;

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
        $dateStart = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'date_start' => $dateStart->format('Y-m-d'),
            'date_end' => fake()->dateTimeBetween($dateStart, '+4 months')->format('Y-m-d'),
        ];
    }
}
