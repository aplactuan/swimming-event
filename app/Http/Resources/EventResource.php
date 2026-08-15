<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
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
            'gender' => $this->gender->value,
            'sort_order' => $this->sort_order,
            'eligibilities' => $this->whenLoaded(
                'eligibilities',
                fn () => EventEligibilityResource::collection($this->eligibilities)->resolve(),
            ),
        ];
    }
}
