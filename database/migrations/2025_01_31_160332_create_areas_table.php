<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->json('coordinates');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('radius');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};