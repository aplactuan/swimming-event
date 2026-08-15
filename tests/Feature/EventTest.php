<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\EventEligibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_an_event(): void
    {
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition);

        $response = $this->post(
            route('events.store', $competition),
            $this->validPayload($classification, $bracket),
        );

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('events', 0);
    }

    public function test_authenticated_users_can_create_an_event_with_eligibilities(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $noviceBracket] = $this->classificationWithBracket($competition, 'Novice', '8-10');
        $developmental = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
            'sort_order' => 2,
        ]);
        $youngBracket = AgeBracket::factory()->youngerAndBelow()->create([
            'classification_id' => $developmental->id,
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('events.store', $competition), [
                'name' => '25m Freestyle',
                'gender' => EventGender::Female->value,
                'eligibilities' => [
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $noviceBracket->id,
                    ],
                    [
                        'classification_id' => $developmental->id,
                        'age_bracket_id' => $youngBracket->id,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'event-created');

        $event = Event::query()->first();

        $this->assertNotNull($event);
        $this->assertSame('25m Freestyle', $event->name);
        $this->assertSame(EventGender::Female, $event->gender);
        $this->assertSame(1, $event->sort_order);
        $this->assertDatabaseCount('event_eligibilities', 2);
    }

    public function test_event_requires_name_gender_and_at_least_one_eligibility(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('events.store', $competition), [
                'name' => '',
                'gender' => 'invalid',
                'eligibilities' => [],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors(['name', 'gender', 'eligibilities']);
    }

    public function test_age_bracket_must_be_effective_for_the_classification(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice] = $this->classificationWithBracket($competition, 'Novice');
        [$developmental, $developmentalBracket] = $this->classificationWithBracket(
            $competition,
            'Developmental',
            '10-15',
            2,
        );

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('events.store', $competition), [
                'name' => '25m Breaststroke',
                'gender' => EventGender::Male->value,
                'eligibilities' => [
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $developmentalBracket->id,
                    ],
                ],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('eligibilities.0.age_bracket_id');

        $this->assertSame($developmental->id, $developmentalBracket->classification_id);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_child_classification_can_use_inherited_parent_age_bracket(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $parent = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
        ]);
        $parentBracket = AgeBracket::factory()->create([
            'classification_id' => $parent->id,
            'name' => '9 and below',
        ]);
        $child = Classification::factory()->childOf($parent)->create([
            'name' => 'Class A',
        ]);

        $this
            ->actingAs($user)
            ->post(route('events.store', $competition), [
                'name' => '25m Breaststroke (Class A)',
                'gender' => EventGender::Male->value,
                'eligibilities' => [
                    [
                        'classification_id' => $child->id,
                        'age_bracket_id' => $parentBracket->id,
                    ],
                ],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('event_eligibilities', [
            'classification_id' => $child->id,
            'age_bracket_id' => $parentBracket->id,
        ]);
    }

    public function test_classification_must_belong_to_the_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$foreignClassification, $foreignBracket] = $this->classificationWithBracket(
            Competition::factory()->create(),
        );

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('events.store', $competition), $this->validPayload(
                $foreignClassification,
                $foreignBracket,
            ))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('eligibilities.0.classification_id');
    }

    public function test_authenticated_users_can_update_an_event(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition);
        $secondBracket = AgeBracket::factory()->olderAndUp()->create([
            'classification_id' => $classification->id,
            'sort_order' => 2,
        ]);
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle',
            'gender' => EventGender::Male,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('events.update', [$competition, $event]), [
                'name' => '50m Freestyle',
                'gender' => EventGender::Mixed->value,
                'eligibilities' => [
                    [
                        'classification_id' => $classification->id,
                        'age_bracket_id' => $secondBracket->id,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'event-updated');

        $event->refresh();

        $this->assertSame('50m Freestyle', $event->name);
        $this->assertSame(EventGender::Mixed, $event->gender);
        $this->assertDatabaseCount('event_eligibilities', 1);
        $this->assertDatabaseHas('event_eligibilities', [
            'event_id' => $event->id,
            'age_bracket_id' => $secondBracket->id,
        ]);
    }

    public function test_event_cannot_be_updated_for_another_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition);
        $event = Event::factory()->create();

        $this
            ->actingAs($user)
            ->put(
                route('events.update', [$competition, $event]),
                $this->validPayload($classification, $bracket),
            )
            ->assertNotFound();
    }

    public function test_authenticated_users_can_delete_an_event(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition);
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $eligibility = EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('events.destroy', [$competition, $event]));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'event-deleted');

        $this->assertModelMissing($event);
        $this->assertModelMissing($eligibility);
    }

    public function test_deleting_a_classification_cascades_event_eligibilities(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition);
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $eligibility = EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('classifications.destroy', [$competition, $classification]));

        $this->assertModelMissing($eligibility);
        $this->assertModelExists($event);
    }

    public function test_competition_show_includes_events_and_eligibilities(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition, 'Novice', '8-10');
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Breaststroke (Novice 1)',
            'gender' => EventGender::Female,
            'sort_order' => 1,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('competition.events', 1)
                ->where('competition.events.0.name', '25m Breaststroke (Novice 1)')
                ->where('competition.events.0.gender', 'female')
                ->where('competition.events.0.eligibilities.0.classification.name', 'Novice')
                ->where('competition.events.0.eligibilities.0.age_bracket.name', '8-10'));
    }

    /**
     * @return array{0: Classification, 1: AgeBracket}
     */
    private function classificationWithBracket(
        Competition $competition,
        string $classificationName = 'Novice',
        string $bracketName = '8-10',
        int $sortOrder = 1,
    ): array {
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => $classificationName,
            'sort_order' => $sortOrder,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
            'name' => $bracketName,
            'sort_order' => 1,
        ]);

        return [$classification, $bracket];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Classification $classification, AgeBracket $bracket): array
    {
        return [
            'name' => '25m Freestyle',
            'gender' => EventGender::Male->value,
            'eligibilities' => [
                [
                    'classification_id' => $classification->id,
                    'age_bracket_id' => $bracket->id,
                ],
            ],
        ];
    }
}
