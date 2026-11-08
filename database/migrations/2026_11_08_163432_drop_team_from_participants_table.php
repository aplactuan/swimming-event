<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $competitionIds = DB::table('participants')
            ->whereNull('team_id')
            ->distinct()
            ->pluck('competition_id');

        foreach ($competitionIds as $competitionId) {
            $teamId = DB::table('teams')
                ->where('competition_id', $competitionId)
                ->where('name', 'Unassigned')
                ->value('id');

            if ($teamId === null) {
                $teamId = (string) Str::uuid();

                DB::table('teams')->insert([
                    'id' => $teamId,
                    'competition_id' => $competitionId,
                    'name' => 'Unassigned',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('participants')
                ->where('competition_id', $competitionId)
                ->whereNull('team_id')
                ->update(['team_id' => $teamId]);
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->uuid('team_id')->nullable(false)->change();
            $table->dropColumn('team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('team')->nullable()->after('gender');
            $table->uuid('team_id')->nullable()->change();
        });

        $participants = DB::table('participants')
            ->join('teams', 'teams.id', '=', 'participants.team_id')
            ->get(['participants.id', 'teams.name']);

        foreach ($participants as $participant) {
            DB::table('participants')
                ->where('id', $participant->id)
                ->update(['team' => $participant->name]);
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->string('team')->nullable(false)->change();
        });
    }
};
