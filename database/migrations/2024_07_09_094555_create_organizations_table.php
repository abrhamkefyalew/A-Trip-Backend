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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('name');
            $table->longText('organization_description')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_approved')->default(0);
            $table->timestamp('email_verified_at')->nullable();

            // if needed , organization profile picture will be added to media table
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
