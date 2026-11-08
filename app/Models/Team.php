<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'competition_id',
    'name',
])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the competition that owns the team.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the participants on the team.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class)
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /**
     * Get the relay events the team is entered in.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withTimestamps();
    }

    /**
     * Get heat lanes assigned to the team (relay lanes).
     */
    public function heatLanes(): HasMany
    {
        return $this->hasMany(HeatLane::class);
    }
}
