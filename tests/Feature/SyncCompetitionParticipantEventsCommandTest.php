<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\EventEligibility;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncCompetitionParticipantEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_attaches_paid_participants_to_matching_events(): void
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

        $paidMatch = Participant::factory()->paid()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2015-06-15',
        ]);
        $unpaidMatch = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2015-06-15',
            'paid' => false,
        ]);
        $paidWrongAge = Participant::factory()->paid()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2010-01-01',
        ]);

        $this
            ->artisan('participants:sync-events', ['competition' => $competition->id])
            ->expectsOutputToContain('Synced 2 paid participant(s)')
            ->expectsOutputToContain('Event entries: 0 → 1.')
            ->assertSuccessful();

        $event->refresh()->load('participants');

        $this->assertTrue($event->participants->contains('id', $paidMatch->id));
        $this->assertFalse($event->participants->contains('id', $unpaidMatch->id));
        $this->assertFalse($event->participants->contains('id', $paidWrongAge->id));
    }

    public function test_it_clears_event_entries_for_unpaid_participants(): void
    {
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $unpaid = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'paid' => false,
        ]);
        $event->participants()->attach($unpaid);

        $this
            ->artisan('participants:sync-events', ['competition' => $competition->id])
            ->expectsOutputToContain('Cleared event entries for 1 unpaid participant(s).')
            ->assertSuccessful();

        $this->assertFalse($event->fresh()->participants()->whereKey($unpaid->id)->exists());
    }

    public function test_it_fails_when_the_competition_does_not_exist(): void
    {
        $missingId = '01a1c94c-4bb2-7292-9744-8af38af29959';

        $this
            ->artisan('participants:sync-events', ['competition' => $missingId])
            ->expectsOutputToContain("Competition [{$missingId}] was not found.")
            ->assertFailed();
    }
}
