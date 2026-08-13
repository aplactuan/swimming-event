<?php

namespace Tests\Feature;

use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeBracketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_an_age_bracket(): void
    {
        $classification = Classification::factory()->create();

        $response = $this->post(route('age-brackets.store', [
            $classification->competition,
            $classification,
        ]), $this->validPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('age_brackets', 0);
    }

    public function test_authenticated_users_can_create_a_closed_age_bracket(): void
    {
        $user = User::factory()->create();
        $classification = Classification::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('age-brackets.store', [
                $classification->competition,
                $classification,
            ]), $this->validPayload());

        $response
            ->assertRedirect(route('competitions.show', $classification->competition))
            ->assertSessionHas('status', 'age-bracket-created');

        $bracket = AgeBracket::query()->first();

        $this->assertNotNull($bracket);
        $this->assertSame('8-10', $bracket->name);
        $this->assertSame('2014-01-01', $bracket->start_birthday->toDateString());
        $this->assertSame('2016-12-31', $bracket->end_birthday->toDateString());
        $this->assertSame(1, $bracket->sort_order);
    }

    public function test_authenticated_users_can_create_open_ended_age_brackets(): void
    {
        $user = User::factory()->create();
        $classification = Classification::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('age-brackets.store', [
                $classification->competition,
                $classification,
            ]), [
                'name' => '6 and below',
                'start_birthday' => '2018-01-01',
                'end_birthday' => '',
            ])
            ->assertSessionHasNoErrors();

        $this
            ->actingAs($user)
            ->post(route('age-brackets.store', [
                $classification->competition,
                $classification,
            ]), [
                'name' => '14 and up',
                'start_birthday' => '',
                'end_birthday' => '2012-12-31',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('age_brackets', 2);
        $this->assertNull(AgeBracket::query()->where('name', '6 and below')->first()?->end_birthday);
        $this->assertNull(AgeBracket::query()->where('name', '14 and up')->first()?->start_birthday);
    }

    public function test_age_bracket_requires_a_name_and_at_least_one_birthday(): void
    {
        $user = User::factory()->create();
        $classification = Classification::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $classification->competition))
            ->post(route('age-brackets.store', [
                $classification->competition,
                $classification,
            ]), [
                'name' => '',
                'start_birthday' => '',
                'end_birthday' => '',
            ])
            ->assertRedirect(route('competitions.show', $classification->competition))
            ->assertSessionHasErrors(['name', 'start_birthday']);
    }

    public function test_end_birthday_cannot_precede_start_birthday(): void
    {
        $user = User::factory()->create();
        $classification = Classification::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $classification->competition))
            ->post(route('age-brackets.store', [
                $classification->competition,
                $classification,
            ]), [
                'name' => 'Invalid',
                'start_birthday' => '2016-12-31',
                'end_birthday' => '2014-01-01',
            ])
            ->assertRedirect(route('competitions.show', $classification->competition))
            ->assertSessionHasErrors('end_birthday');
    }

    public function test_age_bracket_cannot_be_created_for_a_classification_on_another_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('age-brackets.store', [
                $competition,
                $classification,
            ]), $this->validPayload())
            ->assertNotFound();
    }

    public function test_authenticated_users_can_update_an_age_bracket(): void
    {
        $user = User::factory()->create();
        $bracket = AgeBracket::factory()->create(['name' => '8-10']);

        $response = $this
            ->actingAs($user)
            ->put(route('age-brackets.update', [
                $bracket->classification->competition,
                $bracket->classification,
                $bracket,
            ]), $this->validPayload([
                'name' => '8 to 10',
            ]));

        $response
            ->assertRedirect(route('competitions.show', $bracket->classification->competition))
            ->assertSessionHas('status', 'age-bracket-updated');

        $this->assertSame('8 to 10', $bracket->fresh()->name);
    }

    public function test_age_bracket_cannot_be_updated_for_another_classification(): void
    {
        $user = User::factory()->create();
        $classification = Classification::factory()->create();
        $bracket = AgeBracket::factory()->create();

        $this
            ->actingAs($user)
            ->put(route('age-brackets.update', [
                $classification->competition,
                $classification,
                $bracket,
            ]), $this->validPayload())
            ->assertNotFound();
    }

    public function test_authenticated_users_can_delete_an_age_bracket(): void
    {
        $user = User::factory()->create();
        $bracket = AgeBracket::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('age-brackets.destroy', [
                $bracket->classification->competition,
                $bracket->classification,
                $bracket,
            ]));

        $response
            ->assertRedirect(route('competitions.show', $bracket->classification->competition))
            ->assertSessionHas('status', 'age-bracket-deleted');

        $this->assertModelMissing($bracket);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => '8-10',
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
            ...$overrides,
        ];
    }
}
