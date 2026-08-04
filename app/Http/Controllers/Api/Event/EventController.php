<?php

namespace App\Http\Controllers\Api\Event;

use App\Actions\Event\CreateEventAction;
use App\Actions\Event\DeleteEventAction;
use App\Actions\Event\ListEventsAction;
use App\Actions\Event\UpdateEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Event\StoreEventRequest;
use App\Http\Requests\Api\Event\UpdateEventRequest;
use App\Http\Resources\Api\Event\EventResource;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EventController extends Controller
{
    use AuthorizesRequests;

    public function index(ListEventsAction $listEvents): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Event::class);

        return EventResource::collection($listEvents->handle());
    }

    public function show(Event $event): EventResource
    {
        $this->authorize('view', $event);

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request, CreateEventAction $createEvent): JsonResponse
    {
        $event = $createEvent->handle($request->validated());

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        UpdateEventAction $updateEvent,
    ): EventResource {
        $event = $updateEvent->handle($event, $request->validated());

        return new EventResource($event);
    }

    public function destroy(Event $event, DeleteEventAction $deleteEvent): Response
    {
        $this->authorize('delete', $event);

        $deleteEvent->handle($event);

        return response()->noContent();
    }
}
