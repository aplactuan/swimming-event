<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Team;
use Illuminate\Support\Str;

class FindOrCreateTeam
{
    /**
     * Resolve a competition team by name, creating it when missing.
     *
     * Matching is case-insensitive after whitespace is normalized.
     */
    public function handle(Competition $competition, string $name): Team
    {
        $name = Str::squish($name);

        $existing = $competition->teams()
            ->get()
            ->first(fn (Team $team): bool => Str::lower($team->name) === Str::lower($name));

        if ($existing !== null) {
            return $existing;
        }

        return $competition->teams()->create([
            'name' => $name,
        ]);
    }
}
