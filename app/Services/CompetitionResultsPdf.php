<?php

namespace App\Services;

use App\Enums\EventGender;
use App\Enums\HeatLaneStatus;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\HeatLane;
use App\Support\Pdf\ResultsBookPdf;
use Symfony\Component\HttpFoundation\Response;

class CompetitionResultsPdf
{
    public function __construct(
        private CompetitionScoring $scoring,
    ) {}

    /**
     * Stream a Meet Manager-style results PDF for the competition.
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
            ])
            ->when(
                $classification !== null,
                fn ($query) => $query->eligibleFor([
                    'classification_id' => $classification->id,
                ]),
            )
            ->get();

        $sections = $events->map(function (Event $event) use ($competition): array {
            $scored = $this->scoring->scoreEvent($event, $competition);

            return [
                'heading' => $this->eventHeading($event),
                'rows' => $scored->map(fn (array $row): array => $this->rowPayload($row))->all(),
            ];
        })->all();

        $scopeLabel = $classification?->name ?? 'All events';

        return $this->stream(
            competition: $competition,
            sections: $sections,
            scopeLabel: $scopeLabel,
            filenameSuffix: $classification !== null ? '-'.str($classification->name)->slug() : '',
        );
    }

    /**
     * Stream a results PDF for one event, optionally filtered to selected ages.
     *
     * @param  list<int>  $ages
     */
    public function downloadEvent(Competition $competition, Event $event, array $ages = []): Response
    {
        abort_unless($event->competition_id === $competition->id, 404);

        $event->loadMissing(['eligibilities.classification', 'eligibilities.ageBracket']);

        $ages = array_values(array_unique(array_map(
            static fn (mixed $age): int => (int) $age,
            $ages,
        )));
        sort($ages);

        $scored = $this->scoring->scoreEvent($event, $competition);

        if ($ages !== []) {
            $scored = $scored
                ->filter(function (array $row) use ($ages): bool {
                    $participantAge = $row['lane']->participant?->age;

                    return $participantAge !== null && in_array((int) $participantAge, $ages, true);
                })
                ->values();
        }

        $rows = $this->rowsForExport($scored, reRank: $ages !== []);
        $scopeLabel = $ages === []
            ? 'All ages in this event'
            : 'Ages '.implode(', ', $ages);

        return $this->stream(
            competition: $competition,
            sections: [[
                'heading' => $this->eventHeading($event),
                'rows' => $rows,
            ]],
            scopeLabel: $scopeLabel,
            filenameSuffix: '-'.str($event->name)->slug().($ages !== [] ? '-ages-'.implode('-', $ages) : ''),
        );
    }

    /**
     * @param  list<array{heading: string, rows: list<array{place: string, name: string, age: string, team: string, time: string, points: string}>}>  $sections
     */
    private function stream(
        Competition $competition,
        array $sections,
        string $scopeLabel,
        string $filenameSuffix,
    ): Response {
        $generatedAt = now()->format('g:i A  m/d/Y');

        $pdf = new ResultsBookPdf(
            competitionName: $competition->name,
            competitionDate: $competition->competition_date->format('m/d/Y'),
            scopeLabel: $scopeLabel,
            generatedAt: $generatedAt,
        );

        $pdf->build($sections);

        $filename = sprintf(
            '%s-results%s.pdf',
            str($competition->name)->slug(),
            $filenameSuffix,
        );

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{lane: HeatLane, place: int|null, points: float|null, status: string}>  $scored
     * @return list<array{place: string, name: string, age: string, team: string, time: string, points: string}>
     */
    private function rowsForExport($scored, bool $reRank): array
    {
        $place = 0;

        return $scored->map(function (array $row) use ($reRank, &$place): array {
            $payload = $this->rowPayload($row);

            if (! $reRank) {
                return $payload;
            }

            $lane = $row['lane'];
            $isNs = $lane->status === HeatLaneStatus::Ns || $row['status'] === HeatLaneStatus::Ns->value;

            if ($isNs || $lane->finish_time_hundredths === null) {
                $payload['place'] = '---';

                return $payload;
            }

            $place++;
            $payload['place'] = (string) $place;

            return $payload;
        })->all();
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
     * @param  array{lane: HeatLane, place: int|null, points: float|null, status: string}  $row
     * @return array{place: string, name: string, age: string, team: string, time: string, points: string}
     */
    private function rowPayload(array $row): array
    {
        /** @var HeatLane $lane */
        $lane = $row['lane'];
        $isNs = $lane->status === HeatLaneStatus::Ns || $row['status'] === HeatLaneStatus::Ns->value;

        if ($lane->participant !== null) {
            $name = $lane->participant->last_name.', '.$lane->participant->first_name;
            $age = (string) $lane->participant->age;
            $team = $lane->participant->team?->name ?? '';
        } else {
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
        }

        if ($isNs) {
            $time = 'NS';
            $place = '---';
            $points = '';
        } elseif ($lane->finish_time_hundredths === null) {
            $time = 'NT';
            $place = $row['place'] !== null ? (string) $row['place'] : '---';
            $points = $this->formatPoints($row['points']);
        } else {
            $time = $this->formatFinalsTime($lane->finish_time_hundredths);
            $place = $row['place'] !== null ? (string) $row['place'] : '---';
            $points = $this->formatPoints($row['points']);
        }

        return [
            'place' => $place,
            'name' => $name,
            'age' => $age,
            'team' => $team,
            'time' => $time,
            'points' => $points,
        ];
    }

    private function formatFinalsTime(int $hundredths): string
    {
        $minutes = intdiv($hundredths, 6000);
        $seconds = intdiv($hundredths % 6000, 100);
        $frac = $hundredths % 100;

        if ($minutes === 0) {
            return sprintf('%d.%02d', $seconds, $frac);
        }

        return sprintf('%d:%02d.%02d', $minutes, $seconds, $frac);
    }

    private function formatPoints(?float $points): string
    {
        if ($points === null) {
            return '';
        }

        if (fmod($points, 1.0) === 0.0) {
            return (string) (int) $points;
        }

        return rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.');
    }
}
