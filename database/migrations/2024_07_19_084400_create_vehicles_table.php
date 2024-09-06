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

            // SYNTAX - should nullable precede constrained // check first // correct all three nullable foreign ids below 

            $table->foreignId('vehicle_name_id')->constrained('vehicle_names'); // should this be nullable // in my eyes it should NOT be null
            
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers'); // should this be nullable or not // can a vehicle added without any supplier // yes because some vehicles could be owned by Adiamat itself
            $table->foreignId('driver_id')->nullable()->constrained('drivers'); // this is ONE to ONE   only              // so we defined the below unique
            $table->unique('driver_id');  // this unique column is defined because this is ONE to ONE   only relationship // one driver can only be paired with one vehicle


            $table->string('vehicle_name')->nullable(); // should this column exist
            $table->longText('vehicle_description')->nullable();
            $table->string('vehicle_model')->nullable(); // this column must exist
            $table->string('plate_number')->unique()->nullable(); // should this be nullable or required // check abrham // ASK SAMSON
            $table->string('year')->nullable();
            $table->string('is_available')->default(Vehicle::VEHICLE_AVAILABLE); // this column is enum // check if this works // and if using constants this way is the recommended way of doing it

            // if with_driver = 1, the driver_id must be set also, otherwise i should return error
            $table->boolean('with_driver')->default(0); // to know if this vehicle can be rented without driver 
                                                                                                                    // the default is => 0 = the supplier rents this car without a driver   -   this VEHICLE HAS NO DRIVER
                                                                                                                    //                        - the supplier is willing to rent this vehicle with NO driver 
                                                                                                                    //                => 1 = the supplier only wants to rent this car only with his own driver 
                                                                                                                    //                        - means the supplier does NOT want to rent this vehicle without his own driver
                                                                                                                    //
                                                                                                                    // 0 = NO  - (only vehicle is rented),             
                                                                                                                    // 1 = YES - (vehicle rented with my driver only)
                                                                                                                          
            
            $table->foreignId('bank_id')->nullable()->constrained('banks'); // since ADIAMAT might have their own vehicles (that means adiamat will not pay adiamat themselves for their own vehicles), this should be nullable
            $table->string('bank_account')->nullable(); // since ADIAMAT might have their own vehicles (that means adiamat will not pay adiamat themselves for their own vehicles), this should be nullable
            
            // $table->boolean('is_notifiable')->default(1); // for the supplier // did we need this column

            // libre, third_person, power_of_attorney  columns will be in media table

            // address of the car is mandatory because it will let the customer know in what location the car is available // so it will be added in address table

            
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
