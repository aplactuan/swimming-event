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
        Schema::create('relay_lane_swimmers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('heat_lane_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('order');
            $table->timestamps();

            $table->unique(['heat_lane_id', 'order']);
            $table->unique(['heat_lane_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relay_lane_swimmers');
    }
};
