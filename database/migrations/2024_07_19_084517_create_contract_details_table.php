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

            $table->boolean('with_driver')->default(0);     // do we need this // because we already have it in orders ? , or should we use it here and remove it in orders
            $table->boolean('with_fuel')->default(0);

            $table->decimal('price_contract', 10, 2); // the contract winning price // the driver or vehicle_supplier should not see this price
            $table->decimal('price_vehicle_payment', 10, 2); // the price that is to be paid for vehicle_supplier (i.e payed to the vehicle account) // the organization should not see this price
            
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
