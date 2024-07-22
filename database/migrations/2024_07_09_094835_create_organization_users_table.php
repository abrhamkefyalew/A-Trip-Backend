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
        Schema::create('organization_users', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->foreignId('organization_id')->constrained('organizations');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone_number')->unique();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_admin'); // 1 or 0 // to check if the organization_user have admin privilege
            $table->timestamp('email_verified_at')->nullable();
            // $table->string('password');

            // profile picture will be contained in media table
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_users');
    }
};
