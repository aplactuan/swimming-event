<?php

namespace App\Http\Resources;

use App\Models\AgeBracket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AgeBracket
 */
class AgeBracketResource extends JsonResource
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
            'start_birthday' => $this->start_birthday?->toDateString(),
            'end_birthday' => $this->end_birthday?->toDateString(),
            'sort_order' => $this->sort_order,
        ];
    }
}
