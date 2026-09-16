<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_generate_events(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->post(route('events.generate', $competition), [])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Event::query()->count());
    }

    public function test_it_generates_each_selected_gender_and_age_bracket_combination(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $sixAndBelow] = $this->classificationWithBracket(
            $competition,
            'Novice',
            '6 and below',
            '2019-01-01',
            '2026-01-01',
        );
        $sevenToTen = AgeBracket::factory()->create([
            'classification_id' => $novice->id,
            'name' => '7 - 10',
            'start_birthday' => '2015-01-01',
            'end_birthday' => '2018-12-31',
            'sort_order' => 2,
        ]);
        [$developmental] = $this->classificationWithBracket(
            $competition,
            'Developmental',
            '6 and below',
            '2019-01-01',
            '2026-01-01',
            2,
        );

        $response = $this
            ->actingAs($user)
            ->post(route('events.generate', $competition), [
                'name' => '25m Freestyle',
                'genders' => [EventGender::Male->value, EventGender::Female->value],
                'eligibilities' => [
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $sixAndBelow->id,
                    ],
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $sevenToTen->id,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'events-generated')
            ->assertSessionHas('generated_events_count', 4);

        $events = Event::query()
            ->with('eligibilities')
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(4, $events);
        $this->assertSame([
            '25m Freestyle|male|'.$sixAndBelow->id,
            '25m Freestyle|female|'.$sixAndBelow->id,
            '25m Freestyle|male|'.$sevenToTen->id,
            '25m Freestyle|female|'.$sevenToTen->id,
        ], $events->map(fn (Event $event): string => implode('|', [
            $event->name,
            $event->gender->value,
            $event->eligibilities->sole()->age_bracket_id,
        ]))->all());
        $this->assertTrue($events->every(
            fn (Event $event): bool => $event->eligibilities->sole()->classification_id === $novice->id,
        ));
        $this->assertFalse($events->contains(
            fn (Event $event): bool => $event->eligibilities->contains('classification_id', $developmental->id),
        ));
    }

    public function test_generator_rejects_duplicate_or_ineffective_eligibility_pairs(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $noviceBracket] = $this->classificationWithBracket($competition, 'Novice');
        [, $developmentalBracket] = $this->classificationWithBracket(
            $competition,
            'Developmental',
            sortOrder: 2,
        );
        [$foreignClassification, $foreignBracket] = $this->classificationWithBracket(
            Competition::factory()->create(),
            'Foreign',
        );

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('events.generate', $competition), [
                'name' => '25m Freestyle',
                'genders' => [EventGender::Male->value],
                'eligibilities' => [
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $noviceBracket->id,
                    ],
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $noviceBracket->id,
                    ],
                    [
                        'classification_id' => $novice->id,
                        'age_bracket_id' => $developmentalBracket->id,
                    ],
                    [
                        'classification_id' => $foreignClassification->id,
                        'age_bracket_id' => $foreignBracket->id,
                    ],
                ],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors([
                'eligibilities.1.age_bracket_id',
                'eligibilities.2.age_bracket_id',
                'eligibilities.3.classification_id',
            ]);

        $this->assertSame(0, Event::query()->count());
    }

    public function test_generated_events_bulk_attach_only_matching_paid_participants(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        [$novice, $bracket] = $this->classificationWithBracket(
            $competition,
            'Novice',
            '7 - 10',
            '2014-01-01',
            '2017-12-31',
        );
        $matchingMale = $this->participant($competition, $novice, ParticipantGender::Male, true, '2015-06-15');
        $matchingFemale = $this->participant($competition, $novice, ParticipantGender::Female, true, '2015-06-15');
        $unpaidMale = $this->participant($competition, $novice, ParticipantGender::Male, false, '2015-06-15');
        $wrongAgeFemale = $this->participant($competition, $novice, ParticipantGender::Female, true, '2010-06-15');

        $this
            ->actingAs($user)
            ->post(route('events.generate', $competition), [
                'name' => '50m Backstroke',
                'genders' => [EventGender::Male->value, EventGender::Female->value],
                'eligibilities' => [[
                    'classification_id' => $novice->id,
                    'age_bracket_id' => $bracket->id,
                ]],
            ])
            ->assertSessionHasNoErrors();

        $maleEvent = Event::query()->where('gender', EventGender::Male)->firstOrFail();
        $femaleEvent = Event::query()->where('gender', EventGender::Female)->firstOrFail();

        $this->assertTrue($maleEvent->participants->contains('id', $matchingMale->id));
        $this->assertFalse($maleEvent->participants->contains('id', $matchingFemale->id));
        $this->assertFalse($maleEvent->participants->contains('id', $unpaidMale->id));
        $this->assertTrue($femaleEvent->participants->contains('id', $matchingFemale->id));
        $this->assertFalse($femaleEvent->participants->contains('id', $wrongAgeFemale->id));
    }

    /**
     * @return array{0: Classification, 1: AgeBracket}
     */
    private function classificationWithBracket(
        Competition $competition,
        string $classificationName,
        string $bracketName = '8 - 10',
        string $startBirthday = '2014-01-01',
        string $endBirthday = '2016-12-31',
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
            'start_birthday' => $startBirthday,
            'end_birthday' => $endBirthday,
        ]);

        return [$classification, $bracket];
    }

    private function participant(
        Competition $competition,
        Classification $classification,
        ParticipantGender $gender,
        bool $paid,
        string $birthdate,
    ): Participant {
        return Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => $gender,
            'paid' => $paid,
            'birthdate' => $birthdate,
        ]);
    }
}
