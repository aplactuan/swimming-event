<?php

namespace App\Services;

use App\Enums\EventGender;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Collection;

class ParticipantEventSync
{
    /**
     * Attach all competition events that match the participant.
     */
    public function syncForPaid(Participant $participant): void
    {
        $matchingEventIds = $this->matchingEvents($participant)
            ->pluck('id')
            ->all();

        if ($matchingEventIds === []) {
            return;
        }

        $participant->events()->syncWithoutDetaching($matchingEventIds);
    }

    /**
     * Attach all paid participants that match the event.
     */
    public function syncForEvent(Event $event): void
    {
        $matchingParticipantIds = $this->matchingPaidParticipants($event)
            ->pluck('id')
            ->all();

        if ($matchingParticipantIds === []) {
            return;
        }

        $event->participants()->syncWithoutDetaching($matchingParticipantIds);
    }

    /**
     * Remove the participant from all events.
     */
    public function clearEvents(Participant $participant): void
    {
        $participant->events()->detach();
    }

    /**
     * Determine whether the participant matches the event.
     */
    public function matches(Participant $participant, Event $event): bool
    {
        if ($participant->competition_id !== $event->competition_id) {
            return false;
        }

        if (! $this->matchesGender($participant, $event)) {
            return false;
        }

        $event->loadMissing('eligibilities.ageBracket');

        foreach ($event->eligibilities as $eligibility) {
            if ($eligibility->classification_id !== $participant->classification_id) {
                continue;
            }

            $ageBracket = $eligibility->ageBracket;

            if ($ageBracket !== null && $ageBracket->matchesBirthdate($participant->birthdate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Event>
     */
    private function matchingEvents(Participant $participant): Collection
    {
        $events = Event::query()
            ->where('competition_id', $participant->competition_id)
            ->with('eligibilities.ageBracket')
            ->get();

        return $events->filter(
            fn (Event $event): bool => $this->matches($participant, $event),
        )->values();
    }

    /**
     * @return Collection<int, Participant>
     */
    private function matchingPaidParticipants(Event $event): Collection
    {
        $event->loadMissing('eligibilities.ageBracket');

        $participants = Participant::query()
            ->where('competition_id', $event->competition_id)
            ->where('paid', true)
            ->get();

        return $participants->filter(
            fn (Participant $participant): bool => $this->matches($participant, $event),
        )->values();
    }

    private function matchesGender(Participant $participant, Event $event): bool
    {
        if ($event->gender === EventGender::Mixed) {
            return true;
        }

        return $event->gender->value === $participant->gender->value;
    }
}
