<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Participant;
use App\Models\Team;
use App\Models\User;
use App\Support\Pdf\LaneCardsPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionLaneCardsPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_download_lane_cards_pdf(): void
    {
        $competition = Competition::factory()->create();

        $this->get(route('competition-lane-cards.pdf', $competition))
            ->assertRedirect(route('login'));
    }

    public function test_it_downloads_lane_cards_on_legal_paper_in_a_two_by_five_grid(): void
    {
        [$competition] = $this->seedLaneCardMeet(assignedLanes: 11);

        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-lane-cards.pdf', $competition));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString(
            'lane-cards',
            strtolower((string) $response->headers->get('content-disposition')),
        );

        $pdf = new LaneCardsPdf;
        $cards = [];

        for ($index = 1; $index <= 11; $index++) {
            $cards[] = [
                'meet' => 'Sample Meet - 11/14/2026',
                'event' => 'Event 1  Boys 6 and under 25m Freestyle (Novice) - M',
                'heat' => '1',
                'lane' => (string) $index,
                'seed' => 'NT',
                'name' => 'Swimmer, Test '.$index,
                'age' => '6',
                'team' => 'Aqua Club',
            ];
        }

        $pdf->build($cards);

        $this->assertSame(2, $pdf->PageNo());
        $this->assertSame(2, LaneCardsPdf::COLUMNS);
        $this->assertSame(5, LaneCardsPdf::ROWS);
        $this->assertSame(10, LaneCardsPdf::CARDS_PER_PAGE);
        $this->assertSame(215.9, round($pdf->GetPageWidth(), 1));
        $this->assertSame(330.2, round($pdf->GetPageHeight(), 1));
    }

    public function test_it_can_filter_lane_cards_by_classification(): void
    {
        [$competition, $novice, $developmental] = $this->seedLaneCardMeet(assignedLanes: 1);

        $novicePdf = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-lane-cards.pdf', [
                'competition' => $competition,
                'classification_id' => $novice->id,
            ]));

        $novicePdf->assertOk();
        $this->assertStringContainsString(
            'novice',
            strtolower((string) $novicePdf->headers->get('content-disposition')),
        );

        $developmentalPdf = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-lane-cards.pdf', [
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
            ->get(route('competition-lane-cards.pdf', [
                'competition' => $competition,
                'classification_id' => fake()->uuid(),
            ]))
            ->assertNotFound();
    }

    /**
     * @return array{0: Competition, 1: Classification, 2: Classification}
     */
    private function seedLaneCardMeet(int $assignedLanes = 1): array
    {
        $competition = Competition::factory()->create([
            'name' => 'Sample Invitational',
            'competition_date' => '2026-11-14',
            'number_of_lane' => max(5, $assignedLanes),
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
        ]);
        $developmentalBracket = AgeBracket::factory()->create([
            'classification_id' => $developmental->id,
            'name' => '6 and under',
            'start_birthday' => '2019-11-15',
            'end_birthday' => '2026-11-14',
        ]);

        $this->createLaneCardEvent(
            competition: $competition,
            classification: $novice,
            ageBracket: $noviceBracket,
            team: $team,
            sortOrder: 1,
            assignedLanes: $assignedLanes,
            lastNamePrefix: 'Novice',
        );
        $this->createLaneCardEvent(
            competition: $competition,
            classification: $developmental,
            ageBracket: $developmentalBracket,
            team: $team,
            sortOrder: 2,
            assignedLanes: 1,
            lastNamePrefix: 'Developmental',
        );

        return [$competition, $novice, $developmental];
    }

    private function createLaneCardEvent(
        Competition $competition,
        Classification $classification,
        AgeBracket $ageBracket,
        Team $team,
        int $sortOrder,
        int $assignedLanes,
        string $lastNamePrefix,
    ): void {
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle',
            'gender' => EventGender::Male,
            'sort_order' => $sortOrder,
        ]);
        $event->syncEligibilities([
            [
                'classification_id' => $classification->id,
                'age_bracket_id' => $ageBracket->id,
            ],
        ]);

        $heat = Heat::factory()->create([
            'event_id' => $event->id,
            'heat_number' => 1,
        ]);

        for ($laneNumber = 1; $laneNumber <= $assignedLanes; $laneNumber++) {
            $participant = Participant::factory()->create([
                'competition_id' => $competition->id,
                'classification_id' => $classification->id,
                'team_id' => $team->id,
                'gender' => ParticipantGender::Male,
                'birthdate' => '2021-06-01',
                'first_name' => 'Test',
                'last_name' => $lastNamePrefix.$laneNumber,
                'seed_time_hundredths' => 3200 + $laneNumber,
            ]);
            $event->participants()->attach($participant->id);
            $heat->lanes()->create([
                'lane_number' => $laneNumber,
                'participant_id' => $participant->id,
                'entry_event_id' => $event->id,
                'status' => 'swam',
            ]);
        }
    }
}
