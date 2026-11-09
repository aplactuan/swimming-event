<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\EventType;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Participant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionMeetProgramPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_download_meet_program_pdf(): void
    {
        $competition = Competition::factory()->create();

        $this->get(route('competition-program.pdf', $competition))
            ->assertRedirect(route('login'));
    }

    public function test_it_downloads_a_meet_program_pdf_for_all_events(): void
    {
        [$competition] = $this->seedProgramMeet();

        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-program.pdf', $competition));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString(
            'meet-program',
            strtolower((string) $response->headers->get('content-disposition')),
        );
    }

    public function test_it_can_filter_the_meet_program_pdf_by_classification(): void
    {
        [$competition, $novice, $developmental] = $this->seedProgramMeet();

        $novicePdf = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-program.pdf', [
                'competition' => $competition,
                'classification_id' => $novice->id,
            ]));

        $novicePdf->assertOk();
        $novicePdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'novice',
            strtolower((string) $novicePdf->headers->get('content-disposition')),
        );

        $developmentalPdf = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-program.pdf', [
                'competition' => $competition,
                'classification_id' => $developmental->id,
            ]));

        $developmentalPdf->assertOk();
        $this->assertStringContainsString(
            'developmental',
            strtolower((string) $developmentalPdf->headers->get('content-disposition')),
        );
    }

    public function test_unknown_classification_is_not_found(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-program.pdf', [
                'competition' => $competition,
                'classification_id' => fake()->uuid(),
            ]))
            ->assertNotFound();
    }

    public function test_relay_lanes_appear_in_the_meet_program(): void
    {
        $competition = Competition::factory()->create([
            'name' => 'Relay Meet',
            'competition_date' => '2026-11-14',
        ]);
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
            'name' => '11 & Over',
        ]);
        $event = Event::factory()->relay()->create([
            'competition_id' => $competition->id,
            'name' => '4x25 Freestyle',
            'gender' => EventGender::Mixed,
            'sort_order' => 1,
        ]);
        $event->syncEligibilities([
            [
                'classification_id' => $classification->id,
                'age_bracket_id' => $bracket->id,
            ],
        ]);
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Wave Riders',
        ]);
        $event->teams()->attach($team->id);
        $heat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        $heat->lanes()->create([
            'lane_number' => 4,
            'team_id' => $team->id,
            'entry_event_id' => $event->id,
            'status' => 'swam',
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-program.pdf', $competition));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(EventType::Relay, $event->fresh()->type);
    }

    /**
     * @return array{0: Competition, 1: Classification, 2: Classification}
     */
    private function seedProgramMeet(): array
    {
        $competition = Competition::factory()->create([
            'name' => 'Sample Invitational',
            'competition_date' => '2026-11-14',
        ]);
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Aqua Club',
        ]);

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

        $noviceBracket = AgeBracket::factory()->create([
            'classification_id' => $novice->id,
            'name' => '6 and under',
            'start_birthday' => '2019-11-15',
            'end_birthday' => '2026-11-14',
            'sort_order' => 1,
        ]);
        $developmentalBracket = AgeBracket::factory()->create([
            'classification_id' => $developmental->id,
            'name' => '6 and under',
            'start_birthday' => '2019-11-15',
            'end_birthday' => '2026-11-14',
            'sort_order' => 1,
        ]);

        $this->createProgramEvent(
            competition: $competition,
            classification: $novice,
            ageBracket: $noviceBracket,
            team: $team,
            sortOrder: 1,
            name: '25m Freestyle',
            lastName: 'Novice',
        );
        $this->createProgramEvent(
            competition: $competition,
            classification: $developmental,
            ageBracket: $developmentalBracket,
            team: $team,
            sortOrder: 2,
            name: '25m Freestyle',
            lastName: 'Developmental',
        );

        return [$competition, $novice, $developmental];
    }

    private function createProgramEvent(
        Competition $competition,
        Classification $classification,
        AgeBracket $ageBracket,
        Team $team,
        int $sortOrder,
        string $name,
        string $lastName,
    ): void {
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => $name,
            'gender' => EventGender::Male,
            'sort_order' => $sortOrder,
        ]);
        $event->syncEligibilities([
            [
                'classification_id' => $classification->id,
                'age_bracket_id' => $ageBracket->id,
            ],
        ]);

        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'team_id' => $team->id,
            'gender' => ParticipantGender::Male,
            'birthdate' => '2021-06-01',
            'first_name' => 'Test',
            'last_name' => $lastName,
        ]);
        $event->participants()->attach($participant->id);

        $heat = Heat::factory()->create([
            'event_id' => $event->id,
            'heat_number' => 1,
        ]);
        $heat->lanes()->create([
            'lane_number' => 3,
            'participant_id' => $participant->id,
            'entry_event_id' => $event->id,
            'status' => 'swam',
        ]);
    }
}
