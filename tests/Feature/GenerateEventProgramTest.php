<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\ProgramSortColumn;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GenerateEventProgramTest extends TestCase
{
    use RefreshDatabase;

    private const FREESTYLE = '25m Freestyle';

    private const BACKSTROKE = '50m Backstroke';

    public function test_guests_cannot_generate_a_program(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->post(route('events.program', $competition), [])
            ->assertRedirect(route('login'));
    }

    public function test_it_orders_by_classification_then_gender_then_name(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $developmental] = $this->classifications($competition);
        $this->scrambledEvents($competition, $novice, $developmental);

        $response = $this
            ->actingAs($user)
            ->post(route('events.program', $competition), [
                'columns' => [
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::Gender->value,
                    ProgramSortColumn::Name->value,
                    ProgramSortColumn::AgeBracket->value,
                ],
                'gender_order' => [
                    EventGender::Female->value,
                    EventGender::Male->value,
                    EventGender::Mixed->value,
                ],
                'name_order' => [self::FREESTYLE, self::BACKSTROKE],
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'program-generated')
            ->assertSessionHas('ordered_events_count', 8);

        $this->assertSame([
            'Novice|female|'.self::FREESTYLE,
            'Novice|female|'.self::BACKSTROKE,
            'Novice|male|'.self::FREESTYLE,
            'Novice|male|'.self::BACKSTROKE,
            'Developmental|female|'.self::FREESTYLE,
            'Developmental|female|'.self::BACKSTROKE,
            'Developmental|male|'.self::FREESTYLE,
            'Developmental|male|'.self::BACKSTROKE,
        ], $this->programLabels());

        $this->assertSame(
            range(1, 8),
            Event::query()->orderBy('sort_order')->pluck('sort_order')->all(),
        );
    }

    public function test_the_submitted_gender_order_drives_the_program(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $developmental] = $this->classifications($competition);
        $this->scrambledEvents($competition, $novice, $developmental);

        $this
            ->actingAs($user)
            ->post(route('events.program', $competition), [
                'columns' => [
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::Gender->value,
                    ProgramSortColumn::Name->value,
                    ProgramSortColumn::AgeBracket->value,
                ],
                'gender_order' => [
                    EventGender::Male->value,
                    EventGender::Female->value,
                    EventGender::Mixed->value,
                ],
                'name_order' => [self::BACKSTROKE, self::FREESTYLE],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            'Novice|male|'.self::BACKSTROKE,
            'Novice|male|'.self::FREESTYLE,
            'Novice|female|'.self::BACKSTROKE,
            'Novice|female|'.self::FREESTYLE,
            'Developmental|male|'.self::BACKSTROKE,
            'Developmental|male|'.self::FREESTYLE,
            'Developmental|female|'.self::BACKSTROKE,
            'Developmental|female|'.self::FREESTYLE,
        ], $this->programLabels());
    }

    public function test_the_column_priority_changes_the_grouping(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $developmental] = $this->classifications($competition);
        $this->scrambledEvents($competition, $novice, $developmental);

        $this
            ->actingAs($user)
            ->post(route('events.program', $competition), [
                'columns' => [
                    ProgramSortColumn::Name->value,
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::Gender->value,
                    ProgramSortColumn::AgeBracket->value,
                ],
                'gender_order' => [
                    EventGender::Female->value,
                    EventGender::Male->value,
                    EventGender::Mixed->value,
                ],
                'name_order' => [self::BACKSTROKE, self::FREESTYLE],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            'Novice|female|'.self::BACKSTROKE,
            'Novice|male|'.self::BACKSTROKE,
            'Developmental|female|'.self::BACKSTROKE,
            'Developmental|male|'.self::BACKSTROKE,
            'Novice|female|'.self::FREESTYLE,
            'Novice|male|'.self::FREESTYLE,
            'Developmental|female|'.self::FREESTYLE,
            'Developmental|male|'.self::FREESTYLE,
        ], $this->programLabels());
    }

    public function test_the_age_bracket_column_groups_matching_brackets_across_classifications(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $developmental] = $this->classifications($competition);
        $noviceOlder = $this->ageBracket($novice, '7 - 10', 2);
        $developmentalOlder = $this->ageBracket($developmental, '7 - 10', 2);

        $this->event($competition, self::FREESTYLE, EventGender::Female, $developmentalOlder, 1);
        $this->event($competition, self::FREESTYLE, EventGender::Female, $novice->ageBrackets->first(), 2);
        $this->event($competition, self::FREESTYLE, EventGender::Female, $noviceOlder, 3);
        $this->event($competition, self::FREESTYLE, EventGender::Female, $developmental->ageBrackets->first(), 4);

        $this
            ->actingAs($user)
            ->post(route('events.program', $competition), [
                'columns' => [
                    ProgramSortColumn::AgeBracket->value,
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::Gender->value,
                    ProgramSortColumn::Name->value,
                ],
                'gender_order' => [
                    EventGender::Female->value,
                    EventGender::Male->value,
                    EventGender::Mixed->value,
                ],
                'name_order' => [self::FREESTYLE],
            ])
            ->assertSessionHasNoErrors();

        $bracketNames = Event::query()
            ->with('eligibilities.ageBracket', 'eligibilities.classification')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Event $event): string {
                $eligibility = $event->eligibilities->sole();

                return $eligibility->ageBracket->name.'|'.$eligibility->classification->name;
            })
            ->all();

        $this->assertSame([
            '6 and below|Novice',
            '6 and below|Developmental',
            '7 - 10|Novice',
            '7 - 10|Developmental',
        ], $bracketNames);
    }

    public function test_events_with_names_missing_from_the_order_are_placed_last(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice] = $this->classifications($competition);
        $bracket = $novice->ageBrackets->first();

        $this->event($competition, self::FREESTYLE, EventGender::Female, $bracket, 1);
        $this->event($competition, '100m Butterfly', EventGender::Female, $bracket, 2);
        $this->event($competition, self::BACKSTROKE, EventGender::Female, $bracket, 3);

        $this
            ->actingAs($user)
            ->post(route('events.program', $competition), [
                'columns' => [
                    ProgramSortColumn::Name->value,
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::AgeBracket->value,
                    ProgramSortColumn::Gender->value,
                ],
                'gender_order' => [
                    EventGender::Female->value,
                    EventGender::Male->value,
                    EventGender::Mixed->value,
                ],
                'name_order' => [self::BACKSTROKE, self::FREESTYLE],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [self::BACKSTROKE, self::FREESTYLE, '100m Butterfly'],
            Event::query()->orderBy('sort_order')->pluck('name')->all(),
        );
    }

    public function test_it_rejects_incomplete_or_duplicated_orderings(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice] = $this->classifications($competition);
        $this->event(
            $competition,
            self::FREESTYLE,
            EventGender::Female,
            $novice->ageBrackets->first(),
            7,
        );

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('events.program', $competition), [
                'columns' => [
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::Classification->value,
                    ProgramSortColumn::Gender->value,
                    ProgramSortColumn::Name->value,
                ],
                'gender_order' => [EventGender::Female->value],
                'name_order' => [self::FREESTYLE, self::FREESTYLE],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors([
                'columns.1',
                'gender_order',
                'name_order.1',
            ]);

        $this->assertSame(7, Event::query()->sole()->sort_order);
    }

    public function test_the_competition_page_exposes_the_unique_event_names_in_program_order(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice] = $this->classifications($competition);
        $bracket = $novice->ageBrackets->first();

        $this->event($competition, self::BACKSTROKE, EventGender::Female, $bracket, 1);
        $this->event($competition, self::FREESTYLE, EventGender::Female, $bracket, 2);
        $this->event($competition, self::BACKSTROKE, EventGender::Male, $bracket, 3);

        $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->where('event_names', [self::BACKSTROKE, self::FREESTYLE])
                ->etc(),
            );
    }

    /**
     * Two classifications, each with a "6 and below" bracket.
     *
     * @return array{0: Classification, 1: Classification}
     */
    private function classifications(Competition $competition): array
    {
        $novice = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
            'sort_order' => 1,
        ]);
        $developmental = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
            'sort_order' => 2,
        ]);

        $this->ageBracket($novice, '6 and below', 1);
        $this->ageBracket($developmental, '6 and below', 1);

        return [$novice->load('ageBrackets'), $developmental->load('ageBrackets')];
    }

    private function ageBracket(
        Classification $classification,
        string $name,
        int $sortOrder,
    ): AgeBracket {
        return AgeBracket::factory()->create([
            'classification_id' => $classification->id,
            'name' => $name,
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Create both names, both genders and both classifications in a deliberately
     * jumbled sort order so the program has something to fix.
     */
    private function scrambledEvents(
        Competition $competition,
        Classification $novice,
        Classification $developmental,
    ): void {
        $combinations = [
            [$developmental, EventGender::Male, self::BACKSTROKE],
            [$novice, EventGender::Male, self::FREESTYLE],
            [$developmental, EventGender::Female, self::FREESTYLE],
            [$novice, EventGender::Female, self::BACKSTROKE],
            [$developmental, EventGender::Male, self::FREESTYLE],
            [$novice, EventGender::Female, self::FREESTYLE],
            [$developmental, EventGender::Female, self::BACKSTROKE],
            [$novice, EventGender::Male, self::BACKSTROKE],
        ];

        foreach ($combinations as $index => [$classification, $gender, $name]) {
            $this->event(
                $competition,
                $name,
                $gender,
                $classification->ageBrackets->first(),
                $index + 1,
            );
        }
    }

    private function event(
        Competition $competition,
        string $name,
        EventGender $gender,
        AgeBracket $ageBracket,
        int $sortOrder,
    ): Event {
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => $name,
            'gender' => $gender,
            'sort_order' => $sortOrder,
        ]);

        $event->eligibilities()->create([
            'classification_id' => $ageBracket->classification_id,
            'age_bracket_id' => $ageBracket->id,
        ]);

        return $event;
    }

    /**
     * @return list<string>
     */
    private function programLabels(): array
    {
        return Event::query()
            ->with('eligibilities.classification')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Event $event): string => implode('|', [
                $event->eligibilities->sole()->classification->name,
                $event->gender->value,
                $event->name,
            ]))
            ->all();
    }
}
