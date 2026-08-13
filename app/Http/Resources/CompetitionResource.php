<?php

namespace App\Http\Resources;

use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Competition
 */
class CompetitionResource extends JsonResource
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
            'name' => $this->name,
            'venue' => $this->venue,
            'competition_date' => $this->competition_date->toDateString(),
            'warm_up_time' => $this->formatTime($this->warm_up_time),
            'coaches_meeting_time' => $this->formatTime($this->coaches_meeting_time),
            'registration_deadline' => $this->registration_deadline->toDateString(),
            'entry_fee' => $this->entry_fee,
            'classifications' => $this->whenLoaded(
                'rootClassifications',
                fn () => ClassificationResource::collection($this->rootClassifications)->resolve(),
            ),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }
}
