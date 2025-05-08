<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->decimal('wallet', 10, 2)->default(0.00);
            $table->integer('totalOrder')->default(0);
            $table->enum('status', ['active', 'block'])->default('active');
            $table->boolean('is_verify')->default(0);
            $table->boolean('is_approve')->default(0);
            $table->string('otp')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->string('city');
            $table->string('image');
            $table->string('neighborhood');
            $table->string('card_image');
            $table->string('license_image');
            $table->string('license_self_image');
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};