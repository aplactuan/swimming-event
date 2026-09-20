<?php

namespace App\Models;

use App\Enums\EventGender;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'competition_id',
    'name',
    'gender',
    'sort_order',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the next sort order for a competition.
     */
    public static function nextSortOrder(string $competitionId): int
    {
        return (int) static::query()
            ->where('competition_id', $competitionId)
            ->max('sort_order') + 1;
    }

    /**
     * Replace eligibility rows for this event.
     *
     * @param  list<array{classification_id: string, age_bracket_id: string}>  $eligibilities
     */
    public function syncEligibilities(array $eligibilities): void
    {
        $this->eligibilities()->delete();

        foreach ($eligibilities as $eligibility) {
            $this->eligibilities()->create($eligibility);
        }
    }

    /**
     * Get the competition that owns the event.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the eligibility rows for the event.
     */
    public function eligibilities(): HasMany
    {
        return $this->hasMany(EventEligibility::class);
    }

    /**
     * Get the heats for the event, in swim order.
     */
    public function heats(): HasMany
    {
        return $this->hasMany(Heat::class)->orderBy('heat_number');
    }

    /**
     * Get every lane across the event's heats.
     */
    public function heatLanes(): HasManyThrough
    {
        return $this->hasManyThrough(HeatLane::class, Heat::class);
    }

    /**
     * Get the participants entered in the event.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->withTimestamps();
    }

    /**
     * Scope a query to events with the given exact name.
     */
    public function scopeOfName(Builder $query, ?string $name): Builder
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $query;
        }

        return $query->where('name', $name);
    }

    /**
     * Scope a query to events of the given gender.
     */
    public function scopeOfGender(Builder $query, EventGender|string|null $gender): Builder
    {
        $gender = $gender instanceof EventGender ? $gender->value : trim((string) $gender);

        if ($gender === '') {
            return $query;
        }

        return $query->where('gender', $gender);
    }

    /**
     * Scope a query to events with a single eligibility row satisfying every given filter.
     *
     * A classification filter also matches eligibilities on its child classifications,
     * while an age bracket filter matches by name across classifications.
     *
     * @param  array{classification_id?: string|null, age_bracket_name?: string|null}  $filters
     */
    public function scopeEligibleFor(Builder $query, array $filters): Builder
    {
        $classificationId = $filters['classification_id'] ?? null;
        $ageBracketName = $filters['age_bracket_name'] ?? null;

        if ($classificationId === null && $ageBracketName === null) {
            return $query;
        }

        return $query->whereHas(
            'eligibilities',
            function (Builder $eligibilities) use ($classificationId, $ageBracketName): void {
                if ($classificationId !== null) {
                    $eligibilities->where(fn (Builder $scoped): Builder => $scoped
                        ->where('classification_id', $classificationId)
                        ->orWhereHas(
                            'classification',
                            fn (Builder $classification): Builder => $classification
                                ->where('parent_id', $classificationId),
                        ));
                }

                if ($ageBracketName !== null) {
                    $eligibilities->whereHas(
                        'ageBracket',
                        fn (Builder $ageBracket): Builder => $ageBracket->where('name', $ageBracketName),
                    );
                }
            },
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
            'gender' => EventGender::class,
            'sort_order' => 'integer',
        ];
    }
}
