<?php

namespace App\Models;

use App\Enums\ParticipantGender;
use App\Observers\ParticipantObserver;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'competition_id',
    'classification_id',
    'first_name',
    'last_name',
    'gender',
    'team',
    'birthdate',
    'age',
    'paid',
])]
#[ObservedBy([ParticipantObserver::class])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'age' => 0,
        'paid' => false,
    ];

    /**
     * Get the competition that owns the participant.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the classification for the participant.
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    /**
     * Get the events the participant is entered in.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Calculate the participant's age on the day of the competition.
     */
    public function ageOnCompetitionDay(): int
    {
        $competitionDate = Competition::query()
            ->whereKey($this->competition_id)
            ->value('competition_date');

        if ($this->birthdate === null || $competitionDate === null) {
            return 0;
        }

        return max(0, (int) $this->birthdate->diffInYears($competitionDate));
    }

    /**
     * Scope a query to participants matching last name or full name.
     */
    public function scopeSearchByName(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $term = '%'.$search.'%';

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->whereLike('last_name', $term)
                ->orWhereRaw("lower(first_name || ' ' || last_name) like lower(?)", [$term])
                ->orWhereRaw("lower(last_name || ' ' || first_name) like lower(?)", [$term])
                ->orWhereRaw("lower(last_name || ', ' || first_name) like lower(?)", [$term]);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => ParticipantGender::class,
            'birthdate' => 'date',
            'age' => 'integer',
            'paid' => 'boolean',
        ];
    }
}
