<?php

namespace App\Models;

use Database\Factories\CompetitionPointRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'competition_id',
    'place',
    'points',
])]
class CompetitionPointRule extends Model
{
    /** @use HasFactory<CompetitionPointRuleFactory> */
    use HasFactory, HasUuids;

    /**
     * Default place → points for a new competition (1st=8 … 8th=1).
     *
     * @return list<array{place: int, points: int}>
     */
    public static function defaults(): array
    {
        $rules = [];

        for ($place = 1; $place <= 8; $place++) {
            $rules[] = [
                'place' => $place,
                'points' => 9 - $place,
            ];
        }

        return $rules;
    }

    /**
     * Seed the default point rules for a competition when none exist.
     */
    public static function seedDefaultsFor(Competition $competition): void
    {
        if ($competition->pointRules()->exists()) {
            return;
        }

        foreach (self::defaults() as $rule) {
            $competition->pointRules()->create($rule);
        }
    }

    /**
     * Get the competition that owns the rule.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'place' => 'integer',
            'points' => 'integer',
        ];
    }
}
