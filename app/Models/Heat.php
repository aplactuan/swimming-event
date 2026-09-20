<?php

namespace App\Models;

use Database\Factories\HeatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'heat_number',
])]
class Heat extends Model
{
    /** @use HasFactory<HeatFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'heat_number' => 1,
    ];

    /**
     * Get the next heat number for an event.
     */
    public static function nextHeatNumber(string $eventId): int
    {
        return (int) static::query()
            ->where('event_id', $eventId)
            ->max('heat_number') + 1;
    }

    /**
     * Get the event that owns the heat.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the lanes in the heat, in lane order.
     */
    public function lanes(): HasMany
    {
        return $this->hasMany(HeatLane::class)->orderBy('lane_number');
    }

    /**
     * Get the participants swimming in the heat, in lane order.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'heat_lanes')
            ->withPivot(['id', 'lane_number', 'finish_time_hundredths'])
            ->orderBy('heat_lanes.lane_number')
            ->withTimestamps();
    }

    /**
     * Assign a participant to a lane, replacing whoever was in it.
     */
    public function assignLane(int $laneNumber, ?Participant $participant = null): HeatLane
    {
        return $this->lanes()->updateOrCreate(
            ['lane_number' => $laneNumber],
            ['participant_id' => $participant?->id],
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'heat_number' => 'integer',
        ];
    }
}
