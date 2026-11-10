<?php

namespace Tests\Feature;

use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeBracketCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_copy_an_age_bracket(): void
    {
        [$source, $target, $bracket] = $this->seedBracketPair();

        $this->post(route('age-brackets.copy', [
            $source->competition,
            $source,
            $bracket,
        ]), [
            'classification_ids' => [$target->id],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('age_brackets', 1);
    }

    public function test_authenticated_users_can_copy_an_age_bracket_to_another_classification(): void
    {
        [$source, $target, $bracket] = $this->seedBracketPair();

        $this
            ->actingAs(User::factory()->create())
            ->post(route('age-brackets.copy', [
                $source->competition,
                $source,
                $bracket,
            ]), [
                'classification_ids' => [$target->id],
            ])
            ->assertRedirect(route('competitions.show', $source->competition))
            ->assertSessionHas('status', 'age-bracket-copied')
            ->assertSessionHas('copied_age_bracket_count', 1);

        $this->assertDatabaseCount('age_brackets', 2);

        $copy = AgeBracket::query()
            ->where('classification_id', $target->id)
            ->first();

        $this->assertNotNull($copy);
        $this->assertSame($bracket->name, $copy->name);
        $this->assertSame(
            $bracket->start_birthday?->toDateString(),
            $copy->start_birthday?->toDateString(),
        );
        $this->assertSame(
            $bracket->end_birthday?->toDateString(),
            $copy->end_birthday?->toDateString(),
        );
        $this->assertSame(1, $copy->sort_order);
    }

    public function test_an_age_bracket_can_be_copied_to_multiple_classifications(): void
    {
        [$source, $firstTarget, $bracket] = $this->seedBracketPair();
        $secondTarget = Classification::factory()->create([
            'competition_id' => $source->competition_id,
            'name' => 'Advanced',
            'sort_order' => 3,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('age-brackets.copy', [
                $source->competition,
                $source,
                $bracket,
            ]), [
                'classification_ids' => [$firstTarget->id, $secondTarget->id],
            ])
            ->assertSessionHas('copied_age_bracket_count', 2);

        $this->assertDatabaseCount('age_brackets', 3);
        $this->assertSame(1, $firstTarget->ageBrackets()->count());
        $this->assertSame(1, $secondTarget->ageBrackets()->count());
    }

    public function test_copy_requires_at_least_one_target_classification(): void
    {
        [$source, , $bracket] = $this->seedBracketPair();

        $this
            ->actingAs(User::factory()->create())
            ->from(route('competitions.show', $source->competition))
            ->post(route('age-brackets.copy', [
                $source->competition,
                $source,
                $bracket,
            ]), [
                'classification_ids' => [],
            ])
            ->assertRedirect(route('competitions.show', $source->competition))
            ->assertSessionHasErrors('classification_ids');
    }

    public function test_copy_cannot_target_the_source_classification(): void
    {
        [$source, , $bracket] = $this->seedBracketPair();

        $this
            ->actingAs(User::factory()->create())
            ->from(route('competitions.show', $source->competition))
            ->post(route('age-brackets.copy', [
                $source->competition,
                $source,
                $bracket,
            ]), [
                'classification_ids' => [$source->id],
            ])
            ->assertRedirect(route('competitions.show', $source->competition))
            ->assertSessionHasErrors('classification_ids.0');

        $this->assertDatabaseCount('age_brackets', 1);
    }

    public function test_copy_cannot_target_a_classification_from_another_competition(): void
    {
        [$source, , $bracket] = $this->seedBracketPair();
        $foreign = Classification::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->from(route('competitions.show', $source->competition))
            ->post(route('age-brackets.copy', [
                $source->competition,
                $source,
                $bracket,
            ]), [
                'classification_ids' => [$foreign->id],
            ])
            ->assertRedirect(route('competitions.show', $source->competition))
            ->assertSessionHasErrors('classification_ids.0');
    }

    /**
     * @return array{0: Classification, 1: Classification, 2: AgeBracket}
     */
    private function seedBracketPair(): array
    {
        $competition = Competition::factory()->create();
        $source = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
            'sort_order' => 1,
        ]);
        $target = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
            'sort_order' => 2,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $source->id,
            'name' => '6 and under',
            'start_birthday' => '2019-01-01',
            'end_birthday' => '2026-12-31',
            'sort_order' => 1,
        ]);

        return [$source, $target, $bracket];
    }
}
