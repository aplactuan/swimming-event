<?php

namespace App\Models;

use App\Enums\HeatLaneStatus;
use Database\Factories\HeatLaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'heat_id',
    'participant_id',
    'team_id',
    'lane_number',
    'finish_time_hundredths',
    'status',
])]
class HeatLane extends Model
{
    /** @use HasFactory<HeatLaneFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'swam',
    ];

    /**
     * Convert minutes, seconds and hundredths into a storable finish time.
     */
    public static function toHundredths(int $minutes, int $seconds, int $hundredths): int
    {
        return ($minutes * 6000) + ($seconds * 100) + $hundredths;
    }

    /**
     * Parse a m:ss.hh, ss.hh or ss finish time into hundredths.
     *
     * Returns null for a blank value or one that does not parse, which clears
     * any time already recorded for the lane.
     */
    public static function parseFinishTime(?string $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?:(\d{1,2}):)?(\d{1,3})(?:\.(\d{1,2}))?$/', $value, $matches) !== 1) {
            return null;
        }

        return static::toHundredths(
            (int) ($matches[1] ?? 0),
            (int) $matches[2],
            (int) str_pad($matches[3] ?? '0', 2, '0', STR_PAD_RIGHT),
        );
    }

    /**
     * Whether the lane is marked did-not-swim.
     */
    public function isNs(): bool
    {
        return $this->status === HeatLaneStatus::Ns;
    }

    /**
     * Whether the lane has an assigned individual or relay entry.
     */
    public function isAssigned(): bool
    {
        return $this->participant_id !== null || $this->team_id !== null;
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
     * Get the team assigned to the lane (relay events).
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the optional display-only relay lineup for this lane.
     */
    public function relaySwimmers(): HasMany
    {
        return $this->hasMany(RelayLaneSwimmer::class)->orderBy('order');
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
            'status' => HeatLaneStatus::class,
        ];
    }
}
