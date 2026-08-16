<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Http\Resources\CompetitionResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\ParticipantResource;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'event_search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $participantSearch = trim((string) ($validated['participant_search'] ?? ''));
        $eventSearch = trim((string) ($validated['event_search'] ?? ''));

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
            ->searchByName($eventSearch)
            ->paginate(self::LIST_PER_PAGE, ['*'], 'events_page')
            ->withQueryString();

        return Inertia::render('Competitions/Show', [
            'competition' => (new CompetitionResource($competition))->resolve(),
            'participants' => $this->paginatedPayload($participants, ParticipantResource::class),
            'events' => $this->paginatedPayload($events, EventResource::class),
            'filters' => [
                'participant_search' => $participantSearch,
                'event_search' => $eventSearch,
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
