<?php

namespace App\Http\Resources;

use App\Models\HeatLane;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One scored lane of an event, as it appears on the event's result sheet.
 *
 * @property-read HeatLane $lane
 * @property-read int|null $place
 * @property-read float|null $points
 * @property-read string $status
 */
class EventResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var HeatLane $lane */
        $lane = $this->resource['lane'];

        return [
            'id' => $lane->id,
            'heat_number' => $lane->heat->heat_number,
            'lane_number' => $lane->lane_number,
            'finish_time_hundredths' => $lane->finish_time_hundredths,
            'finish_time' => $lane->finish_time,
            'place' => $this->resource['place'],
            'points' => $this->resource['points'],
            'status' => $this->resource['status'],
            'participant' => $lane->participant !== null
                ? (new ParticipantResource($lane->participant))->resolve()
                : null,
            'team' => $lane->team !== null
                ? [
                    'id' => $lane->team->id,
                    'name' => $lane->team->name,
                ]
                : ($lane->participant?->team !== null
                    ? [
                        'id' => $lane->participant->team->id,
                        'name' => $lane->participant->team->name,
                    ]
                    : null),
            'relay_swimmers' => $lane->relationLoaded('relaySwimmers')
                ? $lane->relaySwimmers->map(fn ($swimmer): array => [
                    'order' => $swimmer->order,
                    'participant' => $swimmer->participant !== null
                        ? [
                            'id' => $swimmer->participant->id,
                            'first_name' => $swimmer->participant->first_name,
                            'last_name' => $swimmer->participant->last_name,
                        ]
                        : null,
                ])->values()->all()
                : [],
        ];
    }
}
