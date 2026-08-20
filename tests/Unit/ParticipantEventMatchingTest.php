<?php

namespace Tests\Unit;

use App\Enums\EventGender;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\EventEligibility;
use App\Models\Participant;
use App\Services\ParticipantEventSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantEventMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_when_gender_classification_and_age_bracket_align(): void
    {
        [$participant, $event] = $this->participantAndEvent(
            participantGender: ParticipantGender::Female,
            eventGender: EventGender::Female,
            birthdate: '2015-06-15',
        );

        $this->assertTrue((new ParticipantEventSync)->matches($participant, $event));
    }

    public function test_mixed_events_match_either_gender(): void
    {
        [$participant, $event] = $this->participantAndEvent(
            participantGender: ParticipantGender::Male,
            eventGender: EventGender::Mixed,
            birthdate: '2015-06-15',
        );

        $this->assertTrue((new ParticipantEventSync)->matches($participant, $event));
    }

    public function test_does_not_match_when_gender_differs(): void
    {
        [$participant, $event] = $this->participantAndEvent(
            participantGender: ParticipantGender::Female,
            eventGender: EventGender::Male,
            birthdate: '2015-06-15',
        );

        $this->assertFalse((new ParticipantEventSync)->matches($participant, $event));
    }

    public function test_does_not_match_when_birthdate_is_outside_age_bracket(): void
    {
        [$participant, $event] = $this->participantAndEvent(
            participantGender: ParticipantGender::Female,
            eventGender: EventGender::Female,
            birthdate: '2010-01-01',
        );

        $this->assertFalse((new ParticipantEventSync)->matches($participant, $event));
    }

    public function test_does_not_match_when_classification_differs(): void
    {
        $competition = Competition::factory()->create();
        $novice = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);
        $intermediate = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Intermediate',
            'sort_order' => 2,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $novice->id,
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
        ]);

        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'gender' => EventGender::Female,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $novice->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $intermediate->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2015-06-15',
        ]);

        $this->assertFalse((new ParticipantEventSync)->matches($participant, $event));
    }

    public function test_sync_for_event_attaches_matching_paid_participants_only(): void
    {
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
        ]);

        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'gender' => EventGender::Female,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $matchingPaid = Participant::factory()->paid()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2015-06-15',
        ]);
        $unpaid = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2015-06-15',
            'paid' => false,
        ]);

        (new ParticipantEventSync)->syncForEvent($event->fresh('eligibilities.ageBracket'));

        $event->refresh()->load('participants');

        $this->assertTrue($event->participants->contains('id', $matchingPaid->id));
        $this->assertFalse($event->participants->contains('id', $unpaid->id));
    }

    /**
     * @return array{0: Participant, 1: Event}
     */
    private function participantAndEvent(
        ParticipantGender $participantGender,
        EventGender $eventGender,
        string $birthdate,
    ): array {
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
        ]);

        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'gender' => $eventGender,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => $participantGender,
            'birthdate' => $birthdate,
        ]);

        return [$participant, $event->fresh('eligibilities.ageBracket')];
    }
}
