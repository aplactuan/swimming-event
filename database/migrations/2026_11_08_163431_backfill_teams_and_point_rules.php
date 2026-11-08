<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $competitions = DB::table('competitions')->get(['id']);

        foreach ($competitions as $competition) {
            $teamNames = DB::table('participants')
                ->where('competition_id', $competition->id)
                ->whereNotNull('team')
                ->pluck('team')
                ->map(fn (string $name): string => Str::squish($name))
                ->filter()
                ->unique(fn (string $name): string => Str::lower($name))
                ->values();

            $teamsByKey = [];

            foreach ($teamNames as $name) {
                $id = (string) Str::uuid();

                DB::table('teams')->insert([
                    'id' => $id,
                    'competition_id' => $competition->id,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $teamsByKey[Str::lower($name)] = $id;
            }

            $participants = DB::table('participants')
                ->where('competition_id', $competition->id)
                ->get(['id', 'team']);

            foreach ($participants as $participant) {
                $key = Str::lower(Str::squish((string) $participant->team));

                if ($key === '' || ! isset($teamsByKey[$key])) {
                    continue;
                }

                DB::table('participants')
                    ->where('id', $participant->id)
                    ->update(['team_id' => $teamsByKey[$key]]);
            }

            $existingRules = DB::table('competition_point_rules')
                ->where('competition_id', $competition->id)
                ->exists();

            if (! $existingRules) {
                $rules = [];

                for ($place = 1; $place <= 8; $place++) {
                    $rules[] = [
                        'id' => (string) Str::uuid(),
                        'competition_id' => $competition->id,
                        'place' => $place,
                        'points' => 9 - $place,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('competition_point_rules')->insert($rules);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('participants')->update(['team_id' => null]);
        DB::table('competition_point_rules')->delete();
        DB::table('teams')->delete();
    }
};
