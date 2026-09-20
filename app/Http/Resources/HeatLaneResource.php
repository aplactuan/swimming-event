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
            'finish_time_hundredths' => $this->finish_time_hundredths,
            'finish_time' => $this->finish_time,
            'participant' => $this->whenLoaded(
                'participant',
                fn () => $this->participant === null
                    ? null
                    : (new ParticipantResource($this->participant))->resolve(),
            ),
        ];
    }
}
