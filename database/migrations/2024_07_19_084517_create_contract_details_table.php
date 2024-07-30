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
        Schema::create('contract_details', function (Blueprint $table) {
            $table->id()->from(100000);

            $table->foreignId('contract_id')->constrained('contracts');

            $table->foreignId('vehicle_name_id')->constrained('vehicle_names'); // this should NOT be null

            $table->boolean('with_driver');
            $table->boolean('with_fuel');            
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_details');
    }
};
