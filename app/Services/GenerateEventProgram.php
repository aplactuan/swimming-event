<?php

namespace App\Services;

use App\Enums\ProgramSortColumn;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class GenerateEventProgram
{
    /**
     * Events are written back in chunks of this size.
     */
    private const UPSERT_CHUNK = 500;

    /**
     * Rank used for events that cannot be placed by a given column.
     */
    private const UNRANKED = PHP_INT_MAX;

    /**
     * Reorder a competition's events using a user defined column priority.
     *
     * @param  list<string>  $columns  Sort columns from highest to lowest priority.
     * @param  list<string>  $genderOrder  The order the genders should appear in.
     * @param  list<string>  $nameOrder  The order the event names should appear in.
     * @return int The number of events that were reordered.
     */
    public function generate(
        Competition $competition,
        array $columns,
        array $genderOrder,
        array $nameOrder,
    ): int {
        return DB::transaction(function () use ($competition, $columns, $genderOrder, $nameOrder): int {
            Competition::query()
                ->whereKey($competition->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var EloquentCollection<int, Event> $events */
            $events = Event::query()
                ->whereBelongsTo($competition)
                ->with('eligibilities')
                ->get();

            if ($events->isEmpty()) {
                return 0;
            }

            $classificationRanks = $this->classificationRanks($competition);
            $ageBracketRanks = $this->ageBracketRanks(array_keys($classificationRanks));
            $genderRanks = array_flip($genderOrder);
            $nameRanks = array_flip($nameOrder);
            $sortColumns = array_map(
                fn (string $column): ProgramSortColumn => ProgramSortColumn::from($column),
                $columns,
            );

            $keyed = $events->map(fn (Event $event): array => [
                'event' => $event,
                'key' => $this->sortKey(
                    $event,
                    $sortColumns,
                    $classificationRanks,
                    $ageBracketRanks,
                    $genderRanks,
                    $nameRanks,
                ),
            ])->all();

            usort($keyed, fn (array $a, array $b): int => $a['key'] <=> $b['key']);

            $this->writeSortOrder(array_column($keyed, 'event'));

            return count($keyed);
        });
    }

    /**
     * Rank every classification by its position in the configured hierarchy.
     *
     * Roots come first in sort order, each immediately followed by its children.
     *
     * @return array<string, int>
     */
    private function classificationRanks(Competition $competition): array
    {
        /** @var EloquentCollection<int, Classification> $roots */
        $roots = Classification::query()
            ->whereBelongsTo($competition)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $ranks = [];
        $rank = 0;

        foreach ($roots as $root) {
            $ranks[$root->id] = ++$rank;

            foreach ($root->children as $child) {
                $ranks[$child->id] = ++$rank;
            }
        }

        return $ranks;
    }

    /**
     * Rank age brackets by their configured sort order.
     *
     * Brackets sharing a sort order and name across classifications (for example
     * "6 and below" in both Novice and Developmental) share a rank so they group
     * together when age bracket outranks classification.
     *
     * @param  list<string>  $classificationIds
     * @return array<string, int>
     */
    private function ageBracketRanks(array $classificationIds): array
    {
        $brackets = AgeBracket::query()
            ->whereIn('classification_id', $classificationIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order']);

        $ranks = [];
        $rank = 0;
        $previous = null;

        foreach ($brackets as $bracket) {
            $group = $bracket->sort_order.'|'.$bracket->name;

            if ($group !== $previous) {
                $rank++;
                $previous = $group;
            }

            $ranks[$bracket->id] = $rank;
        }

        return $ranks;
    }

    /**
     * Build the comparable sort key for an event, ordered by column priority.
     *
     * @param  list<ProgramSortColumn>  $columns
     * @param  array<string, int>  $classificationRanks
     * @param  array<string, int>  $ageBracketRanks
     * @param  array<string, int>  $genderRanks
     * @param  array<string, int>  $nameRanks
     * @return list<int|string>
     */
    private function sortKey(
        Event $event,
        array $columns,
        array $classificationRanks,
        array $ageBracketRanks,
        array $genderRanks,
        array $nameRanks,
    ): array {
        $key = [];

        foreach ($columns as $column) {
            $key[] = match ($column) {
                ProgramSortColumn::Classification => $this->eligibilityRank(
                    $event,
                    'classification_id',
                    $classificationRanks,
                ),
                ProgramSortColumn::AgeBracket => $this->eligibilityRank(
                    $event,
                    'age_bracket_id',
                    $ageBracketRanks,
                ),
                ProgramSortColumn::Gender => $genderRanks[$event->gender->value] ?? self::UNRANKED,
                ProgramSortColumn::Name => $nameRanks[$event->name] ?? self::UNRANKED,
            };
        }

        $key[] = $event->name;
        $key[] = $event->id;

        return $key;
    }

    /**
     * The best (lowest) rank across an event's eligibility rows.
     *
     * @param  array<string, int>  $ranks
     */
    private function eligibilityRank(Event $event, string $attribute, array $ranks): int
    {
        $best = self::UNRANKED;

        foreach ($event->eligibilities as $eligibility) {
            $rank = $ranks[$eligibility->{$attribute}] ?? self::UNRANKED;

            if ($rank < $best) {
                $best = $rank;
            }
        }

        return $best;
    }

    /**
     * Persist the new program position of each event.
     *
     * @param  list<Event>  $events
     */
    private function writeSortOrder(array $events): void
    {
        $timestamp = now();
        $rows = [];
        $sortOrder = 0;

        foreach ($events as $event) {
            $rows[] = [
                'id' => $event->id,
                'competition_id' => $event->competition_id,
                'name' => $event->name,
                'gender' => $event->gender->value,
                'sort_order' => ++$sortOrder,
                'created_at' => $event->created_at,
                'updated_at' => $timestamp,
            ];
        }

        foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
            Event::query()->upsert($chunk, ['id'], ['sort_order', 'updated_at']);
        }
    }
}
