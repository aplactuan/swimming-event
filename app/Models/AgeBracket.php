<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AgeBracketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'classification_id',
    'name',
    'start_birthday',
    'end_birthday',
    'sort_order',
])]
class AgeBracket extends Model
{
    /** @use HasFactory<AgeBracketFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the next sort order for a classification.
     */
    public static function nextSortOrder(string $classificationId): int
    {
        return (int) static::query()
            ->where('classification_id', $classificationId)
            ->max('sort_order') + 1;
    }

    /**
     * Determine whether the birthdate falls in this bracket.
     */
    public function matchesBirthdate(CarbonInterface $birthdate): bool
    {
        $date = $birthdate->toDateString();
        $start = $this->start_birthday?->toDateString();
        $end = $this->end_birthday?->toDateString();

        if ($start === null && $end === null) {
            return false;
        }

        if ($start !== null && $end !== null) {
            return $date >= $start && $date <= $end;
        }

        if ($start !== null) {
            return $date >= $start;
        }

        return $date <= $end;
    }

    /**
     * Get the classification that owns the age bracket.
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_birthday' => 'date',
            'end_birthday' => 'date',
            'sort_order' => 'integer',
        ];
    }
}
