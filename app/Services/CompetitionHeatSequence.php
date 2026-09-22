<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use Illuminate\Support\Collection;

/**
 * The competition's heats as one running order, from the first heat of the
 * first event in the program through to the last heat of the last event.
 */
class CompetitionHeatSequence
{
    /**
     * Get every heat in the competition, in program order.
     *
     * @return Collection<int, Heat>
     */
    public function ordered(Competition $competition): Collection
    {
        return $competition->heats()
            ->with([
                'event.eligibilities.classification',
                'event.eligibilities.ageBracket',
            ])
            ->get();
    }

    /**
     * Describe a heat as "Novice · Under 6 · 25m Butterfly · Female · Heat 2".
     */
    public function label(Heat $heat): string
    {
        return $this->eventLabel($heat->event).' · Heat '.$heat->heat_number;
    }

    /**
     * Describe an event as "Novice · Under 6 · 25m Butterfly · Female".
     */
    public function eventLabel(Event $event): string
    {
        $eligibilities = $event->eligibilities
            ->map(fn ($eligibility): string => trim(implode(' · ', array_filter([
                $eligibility->classification?->name,
                $eligibility->ageBracket?->name,
            ]))))
            ->filter()
            ->implode(' / ');

        return implode(' · ', array_filter([
            $eligibilities,
            $event->name,
            ucfirst($event->gender->value),
        ]));
    }
}
