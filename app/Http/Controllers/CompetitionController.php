<?php

namespace App\Http\Controllers;

use App\Enums\EventGender;
use App\Http\Requests\CloseCompetitionRequest;
use App\Http\Requests\OpenCompetitionRequest;
use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Http\Resources\CompetitionResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\ParticipantResource;
use App\Models\AgeBracket;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    private const LIST_PER_PAGE = 10;

    /**
     * Display the specified competition.
     */
    public function show(Request $request, Competition $competition): Response
    {
        $validated = $request->validate([
            'participant_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'event_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'event_classification' => ['sometimes', 'nullable', 'uuid'],
            'event_age_bracket' => ['sometimes', 'nullable', 'string', 'max:100'],
            'event_gender' => ['sometimes', 'nullable', Rule::enum(EventGender::class)],
        ]);

        $participantSearch = trim((string) ($validated['participant_search'] ?? ''));
        $eventName = trim((string) ($validated['event_name'] ?? ''));
        $eventClassification = trim((string) ($validated['event_classification'] ?? ''));
        $eventAgeBracket = trim((string) ($validated['event_age_bracket'] ?? ''));
        $eventGender = trim((string) ($validated['event_gender'] ?? ''));

        $competition->load([
            'rootClassifications.ageBrackets',
            'rootClassifications.children.ageBrackets',
        ]);

        $participants = $competition->participants()
            ->with('classification')
            ->searchByName($participantSearch)
            ->paginate(self::LIST_PER_PAGE, ['*'], 'participants_page')
            ->withQueryString();

        $events = $competition->events()
            ->with([
                'eligibilities.classification',
                'eligibilities.ageBracket',
            ])
            ->withCount('participants')
            ->ofName($eventName)
            ->ofGender($eventGender)
            ->eligibleFor([
                'classification_id' => $eventClassification ?: null,
                'age_bracket_name' => $eventAgeBracket ?: null,
            ])
            ->paginate(self::LIST_PER_PAGE, ['*'], 'events_page')
            ->withQueryString();

        return Inertia::render('Competitions/Show', [
            'competition' => (new CompetitionResource($competition))->resolve(),
            'participants' => $this->paginatedPayload($participants, ParticipantResource::class),
            'events' => $this->paginatedPayload($events, EventResource::class),
            'event_names' => $this->distinctEventNames($competition),
            'age_bracket_names' => $this->distinctAgeBracketNames($competition),
            'filters' => [
                'participant_search' => $participantSearch,
                'event_name' => $eventName,
                'event_classification' => $eventClassification,
                'event_age_bracket' => $eventAgeBracket,
                'event_gender' => $eventGender,
            ],
        ]);
    }

    /**
     * Store a newly created competition.
     */
    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        Competition::create($request->validated());

        return redirect()
            ->route('dashboard')
            ->with('status', 'competition-created');
    }

    /**
     * Update the specified competition.
     */
    public function update(UpdateCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $competition->update($request->validated());

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'competition-updated');
    }

    /**
     * Close the competition, freezing its entries and program.
     */
    public function close(CloseCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $competition->is_close = true;
        $competition->save();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'competition-closed');
    }

    /**
     * Reopen the competition so its entries and program can change again.
     */
    public function open(OpenCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $competition->is_close = false;
        $competition->save();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'competition-opened');
    }

    /**
     * Remove the specified competition.
     */
    public function destroy(Competition $competition): RedirectResponse
    {
        $competition->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'competition-deleted');
    }

    /**
     * The competition's unique event names, in their current program order.
     *
     * @return list<string>
     */
    private function distinctEventNames(Competition $competition): array
    {
        return Event::query()
            ->whereBelongsTo($competition)
            ->select('name')
            ->groupBy('name')
            ->orderByRaw('min(sort_order)')
            ->pluck('name')
            ->all();
    }

    /**
     * The competition's unique age bracket names, in their current display order.
     *
     * @return list<string>
     */
    private function distinctAgeBracketNames(Competition $competition): array
    {
        return AgeBracket::query()
            ->whereHas(
                'classification',
                fn (Builder $classification): Builder => $classification->whereBelongsTo($competition),
            )
            ->select('name')
            ->groupBy('name')
            ->orderByRaw('min(sort_order)')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * @param  LengthAwarePaginator<int, Participant|Event>  $paginator
     * @param  class-string  $resourceClass
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null}}
     */
    private function paginatedPayload(LengthAwarePaginator $paginator, string $resourceClass): array
    {
        return [
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
