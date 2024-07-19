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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->boolean('is_active')->default(1); // if the driver is active in the sys
            $table->boolean('is_available')->default(1); // if the driver is on trip
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // do we need this // check first // check login type

            // drivers license will be contained in media table
            // IF NEEDED identification_card, passport will be contained in media table
            
            $table->timestamps();
            $table->softDeletes();
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
