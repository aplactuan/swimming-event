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
     * Scope a query to events matching name.
     */
    public function scopeSearchByName(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->whereLike('name', '%'.$search.'%');
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
