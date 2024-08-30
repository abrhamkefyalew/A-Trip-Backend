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

            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('vehicle_name'); // this can have GENERAL, for each vehicle types (i.e pickup - GENERAL), // this is for those organization who just want to mention only the just vehicle type in their contract detail
            $table->string('vehicle_description')->nullable();
            $table->unique(['vehicle_type_id', 'vehicle_name']); // they are unique together , but if the admin mis spells the vehicle_name, an unintended duplicated (same) vehicle could end up in this table // so the admin should not mis spell
            // this problem can not be solved by adding another table for the vehicle_name to refer to, // because the super admin can still insert same vehicles with mis spelling by mistake // like chevrolet, chevrolt
            
            // $table->string('vehicle_price')->nullable(); // OPTIONAL // for individual customers // a price that is set that can only be used for the individual customers
            
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
