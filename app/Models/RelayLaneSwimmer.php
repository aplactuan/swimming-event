<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'heat_lane_id',
    'participant_id',
    'order',
])]
class RelayLaneSwimmer extends Model
{
    use HasUuids;

    /**
     * Get the heat lane this lineup entry belongs to.
     */
    public function heatLane(): BelongsTo
    {
        return $this->belongsTo(HeatLane::class);
    }

    /**
     * Get the participant swimming this leg.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
