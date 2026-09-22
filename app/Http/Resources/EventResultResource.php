<?php

namespace App\Http\Resources;

use App\Models\HeatLane;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One swum lane of an event, as it appears on the event's result sheet.
 *
 * @mixin HeatLane
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
        return [
            'id' => $this->id,
            'heat_number' => $this->heat->heat_number,
            'lane_number' => $this->lane_number,
            'finish_time_hundredths' => $this->finish_time_hundredths,
            'finish_time' => $this->finish_time,
            'participant' => (new ParticipantResource($this->participant))->resolve(),
        ];
    }
}
