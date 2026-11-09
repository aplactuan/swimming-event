<?php

namespace App\Services;

use App\Enums\EventGender;
use App\Enums\EventType;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Support\Pdf\MeetProgramPdf;
use Symfony\Component\HttpFoundation\Response;

class CompetitionMeetProgramPdf
{
    /**
     * Stream a Meet Manager-style meet program PDF.
     */
    public function download(Competition $competition, ?Classification $classification = null): Response
    {
        if ($classification !== null) {
            abort_unless(
                $classification->competition_id === $competition->id,
                404,
            );
        }

        $events = $competition->events()
            ->with([
                'eligibilities.classification',
                'eligibilities.ageBracket',
                'heats.lanes.participant.team',
                'heats.lanes.team',
                'heats.lanes.relaySwimmers.participant',
            ])
            ->when(
                $classification !== null,
                fn ($query) => $query->eligibleFor([
                    'classification_id' => $classification->id,
                ]),
            )
            ->get();

        $sections = $events->map(fn (Event $event): array => [
            'heading' => $this->eventHeading($event),
            'heats' => $event->heats
                ->map(fn (Heat $heat): array => $this->heatPayload($event, $heat, $event->heats->count()))
                ->values()
                ->all(),
        ])->all();

        $scopeLabel = $classification?->name ?? 'All events';
        $generatedAt = now()->format('g:i A  m/d/Y');

        $pdf = new MeetProgramPdf(
            competitionName: $competition->name,
            competitionDate: $competition->competition_date->format('m/d/Y'),
            scopeLabel: $scopeLabel,
            generatedAt: $generatedAt,
        );

        $pdf->build($sections);

        $filename = sprintf(
            '%s-meet-program%s.pdf',
            str($competition->name)->slug(),
            $classification !== null ? '-'.str($classification->name)->slug() : '',
        );

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function eventHeading(Event $event): string
    {
        $ages = $event->eligibilities
            ->map(fn ($eligibility): ?string => $eligibility->ageBracket?->name)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $gender = match ($event->gender) {
            EventGender::Male => 'Boys',
            EventGender::Female => 'Girls',
            EventGender::Mixed => 'Mixed',
        };

        return trim(implode('  ', array_filter([
            'Event '.$event->sort_order,
            $gender,
            $ages,
            $event->name,
        ])));
    }

    /**
     * @return array{
     *     label: string,
     *     rows: list<array{lane: string, name: string, age: string, team: string, seed: string}>
     * }
     */
    private function heatPayload(Event $event, Heat $heat, int $heatCount): array
    {
        $rows = $heat->lanes
            ->filter(fn (HeatLane $lane): bool => $lane->isAssigned())
            ->sortBy('lane_number')
            ->values()
            ->map(fn (HeatLane $lane): array => $this->lanePayload($lane, $event))
            ->all();

        return [
            'label' => sprintf('Heat   %d of %d   Finals', $heat->heat_number, $heatCount),
            'rows' => $rows,
        ];
    }

    /**
     * @return array{lane: string, name: string, age: string, team: string, seed: string}
     */
    private function lanePayload(HeatLane $lane, Event $event): array
    {
        if ($event->type === EventType::Relay || $lane->team_id !== null) {
            $name = $lane->team?->name ?? 'Unknown';
            $age = '';
            $team = '';

            if ($lane->relationLoaded('relaySwimmers') && $lane->relaySwimmers->isNotEmpty()) {
                $swimmers = $lane->relaySwimmers
                    ->filter(fn ($swimmer): bool => $swimmer->participant !== null)
                    ->map(fn ($swimmer): string => $swimmer->participant->last_name.', '.$swimmer->participant->first_name)
                    ->implode(' / ');

                if ($swimmers !== '') {
                    $name .= ' ('.$swimmers.')';
                }
            }

            return [
                'lane' => (string) $lane->lane_number,
                'name' => $name,
                'age' => $age,
                'team' => $team,
                'seed' => 'NT',
            ];
        }

        $participant = $lane->participant;

        return [
            'lane' => (string) $lane->lane_number,
            'name' => $participant !== null
                ? $participant->last_name.', '.$participant->first_name
                : 'Unknown',
            'age' => $participant !== null ? (string) $participant->age : '',
            'team' => $participant?->team?->name ?? '',
            'seed' => 'NT',
        ];
    }
}
