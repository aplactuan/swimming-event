<?php

namespace App\Http\Resources;

use App\Models\EventEligibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventEligibility
 */
class EventEligibilityResource extends JsonResource
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
            'classification_id' => $this->classification_id,
            'age_bracket_id' => $this->age_bracket_id,
            'classification' => $this->whenLoaded(
                'classification',
                fn () => [
                    'id' => $this->classification->id,
                    'name' => $this->classification->name,
                ],
            ),
            'age_bracket' => $this->whenLoaded(
                'ageBracket',
                fn () => [
                    'id' => $this->ageBracket->id,
                    'name' => $this->ageBracket->name,
                    'start_birthday' => $this->ageBracket->start_birthday?->toDateString(),
                    'end_birthday' => $this->ageBracket->end_birthday?->toDateString(),
                ],
            ),
        ];
    }
}
