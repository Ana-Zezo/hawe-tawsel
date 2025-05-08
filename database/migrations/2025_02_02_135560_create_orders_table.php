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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('orderNumber')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->enum('order_type', ['inside', 'outside']);
            $table->enum('status_break', ['notBreak ', 'Break']);
            $table->text('product_name');
            $table->enum('cover', ['cover', 'unCover']);
            $table->string('image');
            $table->text('description')->nullable();

            $table->decimal('totalPrice', 10, 2);
            $table->decimal('basePrice', 10, 2);
            $table->decimal('coverPrice', 8, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->enum('status', ['create', 'bookOrder', 'receiveOrder', 'finished', 'back', 'finishedBack', 'cancelled'])->default('create');
            $table->string('secret_key');

            $table->string('pickup_date')->nullable();
            $table->string('delivery_date')->nullable();
            $table->string('pickup_time')->nullable();
            $table->string('delivery_time')->nullable();

            $table->foreignId('area_sender_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('area_receiver_id')->nullable()->constrained('areas')->nullOnDelete();
            //    Save Address
            $table->boolean('save_sender')->default(false);
            $table->boolean('save_receiver')->default(false);

            //Receiver address
            $table->string('name_receiver');
            $table->string('phone_receiver');
            $table->string('country_receiver');
            $table->string('city_receiver');
            $table->string('area_street_receiver');
            $table->string('neighborhood_receiver');
            $table->string('build_number_receiver');
            $table->double('latitude_receiver');
            $table->double('longitude_receiver');

            //Sender address
            $table->string('name_sender');
            $table->string('phone_sender');
            $table->string('country_sender');
            $table->string('city_sender');
            $table->string('area_street_sender');
            $table->string('neighborhood_sender');
            $table->string('build_number_sender');
            $table->double('latitude_sender');
            $table->double('longitude_sender');
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
        Schema::dropIfExists('orders');
    }
};