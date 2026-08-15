<?php

namespace App\Http\Resources;

use App\Models\Classification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Classification
 */
class ClassificationResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'inherits_age_brackets' => $this->when(
                $this->relationLoaded('ageBrackets'),
                fn (): bool => $this->inheritsAgeBrackets(),
            ),
            'age_brackets' => $this->when(
                $this->relationLoaded('ageBrackets'),
                fn () => AgeBracketResource::collection($this->effectiveAgeBrackets())->resolve(),
            ),
            'children' => $this->whenLoaded(
                'children',
                function () {
                    return $this->children->map(function (Classification $child) {
                        if (! $child->relationLoaded('parent')) {
                            $child->setRelation('parent', $this->resource);
                        }

                        return (new self($child))->resolve();
                    })->all();
                },
            ),
        ];
    }
}
