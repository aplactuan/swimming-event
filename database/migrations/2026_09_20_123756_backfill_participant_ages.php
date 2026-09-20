<?php

use App\Models\Participant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Compute the age on competition day for participants created before the column existed.
     */
    public function up(): void
    {
        Participant::query()->chunkById(200, function ($participants): void {
            foreach ($participants as $participant) {
                $participant->age = $participant->ageOnCompetitionDay();
                $participant->saveQuietly();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Participant::query()->update(['age' => 0]);
    }
};
