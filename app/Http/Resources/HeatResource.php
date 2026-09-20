<?php

namespace App\Http\Resources;

use App\Models\Heat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Heat
 */
class HeatResource extends JsonResource
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
            'heat_number' => $this->heat_number,
            'lanes' => $this->whenLoaded(
                'lanes',
                fn () => HeatLaneResource::collection($this->lanes)->resolve(),
            ),
        ];
    }
}
