<?php

namespace App\Http\Resources;

use App\Models\HeatLane;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HeatLane
 */
class HeatLaneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lane_number' => $this->lane_number,
            'participant_id' => $this->participant_id,
            'team_id' => $this->team_id,
            'entry_event_id' => $this->entry_event_id,
            'finish_time_hundredths' => $this->finish_time_hundredths,
            'finish_time' => $this->finish_time,
            'status' => $this->status->value,
            'participant' => $this->whenLoaded(
                'participant',
                fn () => $this->participant === null
                    ? null
                    : (new ParticipantResource($this->participant))->resolve(),
            ),
            'team' => $this->whenLoaded(
                'team',
                fn () => $this->team === null
                    ? null
                    : [
                        'id' => $this->team->id,
                        'name' => $this->team->name,
                    ],
            ),
            'entry_event' => $this->whenLoaded(
                'entryEvent',
                fn () => $this->entryEvent === null
                    ? null
                    : [
                        'id' => $this->entryEvent->id,
                        'name' => $this->entryEvent->name,
                        'gender' => $this->entryEvent->gender->value,
                        'eligibilities' => $this->entryEvent->relationLoaded('eligibilities')
                            ? EventEligibilityResource::collection($this->entryEvent->eligibilities)->resolve()
                            : [],
                    ],
            ),
            'relay_swimmers' => $this->whenLoaded(
                'relaySwimmers',
                fn () => $this->relaySwimmers->map(fn ($swimmer): array => [
                    'order' => $swimmer->order,
                    'participant_id' => $swimmer->participant_id,
                    'participant' => $swimmer->participant !== null
                        ? [
                            'id' => $swimmer->participant->id,
                            'first_name' => $swimmer->participant->first_name,
                            'last_name' => $swimmer->participant->last_name,
                        ]
                        : null,
                ])->values()->all(),
            ),
        ];
    }
}
