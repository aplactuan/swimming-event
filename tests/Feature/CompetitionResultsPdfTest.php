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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionResultsPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_download_results_pdf(): void
    {
        $competition = Competition::factory()->create();

        $this->get(route('competition-results.pdf', $competition))
            ->assertRedirect(route('login'));
    }

    public function test_it_downloads_a_pdf_for_all_events(): void
    {
        [$competition] = $this->seedTimedMeet();

        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.pdf', $competition));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_it_can_filter_the_pdf_by_classification(): void
    {
        [$competition, $novice, $developmental] = $this->seedTimedMeet();

        $novicePdf = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.pdf', [
                'competition' => $competition,
                'classification_id' => $novice->id,
            ]));

        $novicePdf->assertOk();
        $novicePdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('novice', strtolower($novicePdf->headers->get('content-disposition')));

        $developmentalPdf = $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.pdf', [
                'competition' => $competition,
                'classification_id' => $developmental->id,
            ]));

        $developmentalPdf->assertOk();
        $this->assertStringContainsString('developmental', strtolower($developmentalPdf->headers->get('content-disposition')));
    }

    public function test_unknown_classification_is_not_found(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.pdf', [
                'competition' => $competition,
                'classification_id' => fake()->uuid(),
            ]))
            ->assertNotFound();
    }

    /**
     * @return array{0: Competition, 1: Classification, 2: Classification}
     */
    private function seedTimedMeet(): array
    {
        $competition = Competition::factory()->create([
            'name' => 'Sample Invitational',
            'competition_date' => '2026-11-14',
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);

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

        $this->createTimedEvent(
            competition: $competition,
            classification: $novice,
            ageBracket: $noviceBracket,
            team: $team,
            sortOrder: 1,
            name: '25m Freestyle',
        );
        $this->createTimedEvent(
            competition: $competition,
            classification: $developmental,
            ageBracket: $developmentalBracket,
            team: $team,
            sortOrder: 2,
            name: '25m Freestyle',
        );

        return [$competition, $novice, $developmental];
    }

    private function createTimedEvent(
        Competition $competition,
        Classification $classification,
        AgeBracket $ageBracket,
        Team $team,
        int $sortOrder,
        string $name,
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
            'last_name' => $classification->name,
        ]);

        $heat = Heat::factory()->create(['event_id' => $event->id]);
        $heat->lanes()->create([
            'lane_number' => 1,
            'participant_id' => $participant->id,
            'finish_time_hundredths' => 2800,
            'status' => 'swam',
        ]);
    }
}
