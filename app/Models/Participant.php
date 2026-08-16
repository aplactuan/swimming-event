<?php

namespace App\Models;

use App\Enums\ParticipantGender;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
    'paid',
])]
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
            'paid' => 'boolean',
        ];
    }
}
