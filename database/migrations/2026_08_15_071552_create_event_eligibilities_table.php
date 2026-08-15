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
        Schema::create('event_eligibilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('classification_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('age_bracket_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'classification_id', 'age_bracket_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_eligibilities');
    }
};
