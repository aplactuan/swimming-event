<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('heat_lanes', function (Blueprint $table) {
            $table->foreignUuid('team_id')
                ->nullable()
                ->after('participant_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('status')->default('swam')->after('finish_time_hundredths');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('heat_lanes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('status');
        });
    }
};
