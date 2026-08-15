<?php

namespace App\Models;

use Database\Factories\ClassificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'competition_id',
    'parent_id',
    'name',
    'sort_order',
])]
class Classification extends Model
{
    /** @use HasFactory<ClassificationFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the next sort order among siblings.
     */
    public static function nextSortOrder(string $competitionId, ?string $parentId): int
    {
        $query = static::query()->where('competition_id', $competitionId);

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return (int) $query->max('sort_order') + 1;
    }

    /**
     * Get the competition that owns the classification.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the parent classification.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Classification::class, 'parent_id');
    }

    /**
     * Get the child classifications.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Classification::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the age brackets for the classification.
     */
    public function ageBrackets(): HasMany
    {
        return $this->hasMany(AgeBracket::class)->orderBy('sort_order');
    }

    /**
     * Age brackets for matching/display, falling back to the parent when unset.
     *
     * @return Collection<int, AgeBracket>
     */
    public function effectiveAgeBrackets(): Collection
    {
        $own = $this->ownAgeBrackets();

        if ($own->isNotEmpty() || $this->parent_id === null) {
            return $own;
        }

        $parent = $this->relationLoaded('parent')
            ? $this->parent
            : $this->parent()->with('ageBrackets')->first();

        if ($parent === null) {
            return $own;
        }

        return $parent->relationLoaded('ageBrackets')
            ? $parent->ageBrackets
            : $parent->ageBrackets()->get();
    }

    /**
     * Whether this classification is using its parent's age brackets.
     */
    public function inheritsAgeBrackets(): bool
    {
        return $this->parent_id !== null && $this->ownAgeBrackets()->isEmpty();
    }

    /**
     * @return Collection<int, AgeBracket>
     */
    private function ownAgeBrackets(): Collection
    {
        return $this->relationLoaded('ageBrackets')
            ? $this->ageBrackets
            : $this->ageBrackets()->get();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
