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
        Schema::create('age_brackets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('classification_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_birthday')->nullable();
            $table->date('end_birthday')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['classification_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('age_brackets');
    }
};
