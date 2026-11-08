<?php

namespace Tests\Feature;

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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompetitionStandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_mvp_is_grouped_by_classification_age_bracket_and_gender(): void
    {
        $competition = Competition::factory()->create([
            'competition_date' => '2026-11-14',
        ]);
        $teamA = Team::factory()->create(['competition_id' => $competition->id, 'name' => 'Alpha']);
        $teamB = Team::factory()->create(['competition_id' => $competition->id, 'name' => 'Beta']);

        $developmental = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
            'sort_order' => 1,
        ]);
        $underSix = AgeBracket::factory()->create([
            'classification_id' => $developmental->id,
            'name' => '6 and under',
            'start_birthday' => '2019-11-15',
            'end_birthday' => '2026-11-14',
            'sort_order' => 1,
        ]);
        $ageSeven = AgeBracket::factory()->create([
            'classification_id' => $developmental->id,
            'name' => '7',
            'start_birthday' => '2018-11-15',
            'end_birthday' => '2019-11-14',
            'sort_order' => 2,
        ]);

        $maleUnderSixA = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $developmental->id,
            'team_id' => $teamA->id,
            'first_name' => 'Max',
            'last_name' => 'MaleA',
            'gender' => ParticipantGender::Male,
            'birthdate' => '2021-06-01',
        ]);
        $maleUnderSixB = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $developmental->id,
            'team_id' => $teamB->id,
            'first_name' => 'Ned',
            'last_name' => 'MaleB',
            'gender' => ParticipantGender::Male,
            'birthdate' => '2020-06-01',
        ]);
        $femaleUnderSix = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $developmental->id,
            'team_id' => $teamA->id,
            'first_name' => 'Fay',
            'last_name' => 'Female',
            'gender' => ParticipantGender::Female,
            'birthdate' => '2021-03-01',
        ]);
        $maleSeven = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $developmental->id,
            'team_id' => $teamB->id,
            'first_name' => 'Sam',
            'last_name' => 'Seven',
            'gender' => ParticipantGender::Male,
            'birthdate' => '2019-01-01',
        ]);

        $maleUnderSixEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'type' => EventType::Individual,
            'sort_order' => 1,
        ]);
        $maleUnderSixEvent->syncEligibilities([
            [
                'classification_id' => $developmental->id,
                'age_bracket_id' => $underSix->id,
            ],
        ]);
        $maleHeat = Heat::factory()->create(['event_id' => $maleUnderSixEvent->id]);
        $maleHeat->lanes()->create([
            'lane_number' => 1,
            'participant_id' => $maleUnderSixA->id,
            'finish_time_hundredths' => 2800,
            'status' => 'swam',
        ]);
        $maleHeat->lanes()->create([
            'lane_number' => 2,
            'participant_id' => $maleUnderSixB->id,
            'finish_time_hundredths' => 3000,
            'status' => 'swam',
        ]);

        $femaleUnderSixEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'type' => EventType::Individual,
            'sort_order' => 2,
        ]);
        $femaleUnderSixEvent->syncEligibilities([
            [
                'classification_id' => $developmental->id,
                'age_bracket_id' => $underSix->id,
            ],
        ]);
        $femaleHeat = Heat::factory()->create(['event_id' => $femaleUnderSixEvent->id]);
        $femaleHeat->lanes()->create([
            'lane_number' => 1,
            'participant_id' => $femaleUnderSix->id,
            'finish_time_hundredths' => 2900,
            'status' => 'swam',
        ]);

        $maleSevenEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'type' => EventType::Individual,
            'sort_order' => 3,
        ]);
        $maleSevenEvent->syncEligibilities([
            [
                'classification_id' => $developmental->id,
                'age_bracket_id' => $ageSeven->id,
            ],
        ]);
        $sevenHeat = Heat::factory()->create(['event_id' => $maleSevenEvent->id]);
        $sevenHeat->lanes()->create([
            'lane_number' => 1,
            'participant_id' => $maleSeven->id,
            'finish_time_hundredths' => 2700,
            'status' => 'swam',
        ]);

        $relay = Event::factory()->relay()->create([
            'competition_id' => $competition->id,
            'sort_order' => 4,
        ]);
        $relay->teams()->attach([$teamB->id, $teamA->id]);
        $relayHeat = Heat::factory()->create(['event_id' => $relay->id]);
        $relayHeat->lanes()->create([
            'lane_number' => 1,
            'team_id' => $teamB->id,
            'finish_time_hundredths' => 5000,
            'status' => 'swam',
        ]);
        $relayHeat->lanes()->create([
            'lane_number' => 2,
            'team_id' => $teamA->id,
            'finish_time_hundredths' => 5200,
            'status' => 'swam',
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-standings.index', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Standings/Show')
                ->has('mvp', 3)
                ->where('mvp.0.classification', 'Developmental')
                ->where('mvp.0.age_bracket', '6 and under')
                ->where('mvp.0.gender', 'female')
                ->where('mvp.0.standings.0.participant_id', $femaleUnderSix->id)
                ->where('mvp.0.standings.0.points', 8)
                ->where('mvp.1.age_bracket', '6 and under')
                ->where('mvp.1.gender', 'male')
                ->where('mvp.1.standings.0.participant_id', $maleUnderSixA->id)
                ->where('mvp.1.standings.0.points', 8)
                ->where('mvp.1.standings.1.participant_id', $maleUnderSixB->id)
                ->where('mvp.1.standings.1.points', 7)
                ->where('mvp.2.age_bracket', '7')
                ->where('mvp.2.gender', 'male')
                ->where('mvp.2.standings.0.participant_id', $maleSeven->id)
                ->where('mvp.2.standings.0.points', 8)
                ->where('teams.0.points', 23)
                ->where('teams.1.points', 23));
    }

    public function test_public_standings_require_a_closed_competition(): void
    {
        $open = Competition::factory()->create();
        $closed = Competition::factory()->closed()->create();

        $this->get(route('public-standings.index', $open))->assertNotFound();

        $this->get(route('public-standings.index', $closed))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Standings')
                ->where('public', true));
    }
}
