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
        Schema::create('heats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('heat_number')->default(1);
            $table->timestamps();

            $table->unique(['event_id', 'heat_number']);
        });

        Schema::create('heat_lanes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('heat_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('participant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('lane_number');
            $table->unsignedInteger('finish_time_hundredths')->nullable();
            $table->timestamps();

            $table->unique(['heat_id', 'lane_number']);
            $table->index(['heat_id', 'finish_time_hundredths']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heat_lanes');
        Schema::dropIfExists('heats');
    }
};
