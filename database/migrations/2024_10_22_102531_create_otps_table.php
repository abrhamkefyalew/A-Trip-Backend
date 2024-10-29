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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_user_id')->nullable()->constrained('organization_users');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('driver_id')->nullable()->constrained('drivers'); 
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');

            $table->string('code');

            $table->timestamp('expiry_time');

            // $table->boolean('is_verified')->default(false); currently we do NOT need this column for OTP

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
