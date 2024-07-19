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
        Schema::create('vehicle_names', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('vehicle_name')->unique();
            $table->string('vehicle_description')->nullable();
            // $table->string('vehicle_price')->nullable(); // OPTIONAL // for individual customers
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_names');
    }
};
