<?php

use App\Models\Vehicle;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id()->from(10000);

            // should the below two columns be mutually exclusive // check first // BUT a vehicle table can have both // so it can have both
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types'); // should nullable precede constrained // check first // correct all three nullable foreign ids below 
            $table->foreignId('vehicle_name_id')->nullable()->constrained('vehicle_names');
            
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers'); // should this be nullable or not // can a vehicle added without any supplier
            $table->foreignId('driver_id')->nullable()->constrained('drivers'); // this is one to one   only
            $table->string('vehicle_name');
            $table->string('vehicle_discription')->nullable();
            $table->string('vehicle_model')->nullable(); // should this be nullabe
            $table->string('plate_number')->unique()->nullable(); // should this be nullable or required // check
            $table->string('year')->nullable();
            $table->string('is_active')->default(Vehicle::VEHICLE_AVAILABLE); // this column is enum // check if this works // and if using constants this way is the recommended way of doing it
            $table->boolean('without_driver'); // can this vehicle be rented without driver
            // $table->boolean('is_notifiable')->default(1); // for the supplier // did we need this column

            // libre, third_person, power_of_attorney  columns will be in media table

            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
