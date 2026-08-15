<?php

namespace App\Http\Resources;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participant
 */
class ParticipantResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender->value,
            'team' => $this->team,
            'birthdate' => $this->birthdate->toDateString(),
            'classification_id' => $this->classification_id,
            'paid' => $this->paid,
            'classification' => $this->whenLoaded(
                'classification',
                fn () => [
                    'id' => $this->classification->id,
                    'name' => $this->classification->name,
                ],
            ),
        ];
    }
}
