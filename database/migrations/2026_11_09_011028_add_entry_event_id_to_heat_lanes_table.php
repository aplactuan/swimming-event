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
            $table->foreignUuid('entry_event_id')
                ->nullable()
                ->after('team_id')
                ->constrained('events')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('heat_lanes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entry_event_id');
        });
    }
};
