<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'classification_id',
    'age_bracket_id',
])]
class EventEligibility extends Model
{
    use HasUuids;

    /**
     * Get the event that owns the eligibility.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the classification for the eligibility.
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    /**
     * Get the age bracket for the eligibility.
     */
    public function ageBracket(): BelongsTo
    {
        return $this->belongsTo(AgeBracket::class);
    }
}
