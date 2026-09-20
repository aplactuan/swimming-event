<?php

namespace App\Models;

use Database\Factories\HeatLaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'heat_id',
    'participant_id',
    'lane_number',
    'finish_time_hundredths',
])]
class HeatLane extends Model
{
    /** @use HasFactory<HeatLaneFactory> */
    use HasFactory, HasUuids;

    /**
     * Convert minutes, seconds and hundredths into a storable finish time.
     */
    public static function toHundredths(int $minutes, int $seconds, int $hundredths): int
    {
        return ($minutes * 6000) + ($seconds * 100) + $hundredths;
    }

    /**
     * Get the heat the lane belongs to.
     */
    public function heat(): BelongsTo
    {
        return $this->belongsTo(Heat::class);
    }

    /**
     * Get the participant swimming the lane, if the lane is assigned.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Record the finish time from its minute, second and hundredth parts.
     */
    public function recordFinishTime(int $minutes, int $seconds, int $hundredths): void
    {
        $this->finish_time_hundredths = static::toHundredths($minutes, $seconds, $hundredths);
    }

    /**
     * Get the finish time formatted as m:ss.hh, or null when the lane has no time.
     */
    public function getFinishTimeAttribute(): ?string
    {
        if ($this->finish_time_hundredths === null) {
            return null;
        }

        $total = $this->finish_time_hundredths;

        return sprintf(
            '%d:%02d.%02d',
            intdiv($total, 6000),
            intdiv($total % 6000, 100),
            $total % 100,
        );
    }

    /**
     * Scope a query to lanes that have a recorded finish time, fastest first.
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query
            ->whereNotNull('finish_time_hundredths')
            ->reorder('finish_time_hundredths');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lane_number' => 'integer',
            'finish_time_hundredths' => 'integer',
        ];
    }
}
