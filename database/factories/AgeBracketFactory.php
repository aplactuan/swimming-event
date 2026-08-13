<?php

namespace Database\Factories;

use App\Models\AgeBracket;
use App\Models\Classification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgeBracket>
 */
class AgeBracketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classification_id' => Classification::factory(),
            'name' => '8-10',
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
            'sort_order' => 1,
        ];
    }

    /**
     * A younger open-ended bracket (start set, end null).
     */
    public function youngerAndBelow(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => '6 and below',
            'start_birthday' => '2018-01-01',
            'end_birthday' => null,
        ]);
    }

    /**
     * An older open-ended bracket (start null, end set).
     */
    public function olderAndUp(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => '14 and up',
            'start_birthday' => null,
            'end_birthday' => '2012-12-31',
        ]);
    }
}
