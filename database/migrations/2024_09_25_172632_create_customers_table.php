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
        Schema::create('customers', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->unique();

            $table->boolean('is_active')->default(1); // if the customer is active in the sys // the CUSTOMER can TOGGLE this depending on his availability 
            $table->boolean('is_approved')->default(0); // should the customer be approved before he can operate in the system     // should this column exist

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // check first // check login type

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
