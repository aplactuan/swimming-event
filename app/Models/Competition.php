<?php

namespace App\Models;

use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'venue',
    'competition_date',
    'warm_up_time',
    'coaches_meeting_time',
    'registration_deadline',
    'entry_fee',
])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get all classifications for the competition.
     */
    public function classifications(): HasMany
    {
        return $this->hasMany(Classification::class);
    }

    /**
     * Get the root classifications for the competition.
     */
    public function rootClassifications(): HasMany
    {
        return $this->hasMany(Classification::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    /**
     * Scope a query to upcoming competitions, soonest first.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereDate('competition_date', '>=', now()->toDateString())
            ->orderBy('competition_date')
            ->orderBy('name');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'competition_date' => 'date',
            'registration_deadline' => 'date',
            'entry_fee' => 'integer',
        ];
    }
}
