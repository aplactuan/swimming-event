<?php

namespace App\Http\Resources;

use App\Models\CompetitionPointRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CompetitionPointRule
 */
class CompetitionPointRuleResource extends JsonResource
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
            'place' => $this->place,
            'points' => $this->points,
        ];
    }
}
