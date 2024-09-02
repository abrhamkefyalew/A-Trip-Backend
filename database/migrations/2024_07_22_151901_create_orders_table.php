<?php

use App\Models\Order;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('order_code'); // will be used if the a multiple vehicle order is made at once in one order request // so for them we use the same order code // it is not unique

            $table->foreignId('organization_id')->constrained('organizations'); // if super_admin updates orders table , he should not update this organization_id column // updating it may create a problem // so updating this is not a good idea
            $table->foreignId('contract_detail_id')->constrained('contract_details');

            $table->foreignId('vehicle_name_id')->constrained('vehicle_names'); // this should NOT be null

            // should nullable come before constrained or after // check first please
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles'); // this is NULL when the order is made initially
            $table->foreignId('driver_id')->nullable()->constrained('drivers'); // this is NULL when the order is made initially
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers'); // this is NULL when the order is made initially

            $table->date('start_date');    // if start_date is not mentioned OPTIONAL  // $table->date('start_date')->useCurrent(); OPTIONAL // is the OPTIONAL code insert it as default value CHECK
            $table->date('end_date'); // if the order is terminated , the order end_date will be assigned with the order termination date, and the original_end_date column will keep this original order end_date value 
                                                                                                                                                // (because original_end_date column had been assigned the order end_date when the order is created initially)
                                                                                                                                                        // as (original_end_date = the original order end_date value i.e [original_end_date = end_date] ) 
            // the start_date and end_date must be less than the Contract end_date
            // the start_date and end_date must be greater than the Contract start_date

            $table->string('start_location')->nullable(); // should this be nullable // described by words
            $table->string('end_location')->nullable(); // should this be nullable // described by words

            // for latitude and longitude values, using DOUBLE data type generally a suitable choice // double is suitable for data types such as scientific calculations or geographical coordinates
            $table->double('start_latitude', 15, 10)->nullable();
            $table->double('start_longitude', 15, 10)->nullable();
            $table->double('end_latitude', 15, 10)->nullable();
            $table->double('end_longitude', 15, 10)->nullable();
            
            $table->string('status')->default(Order::ORDER_STATUS_PENDING); // this column is enum //
            
            $table->boolean('is_terminated')->default(0);
            $table->date('original_end_date')->nullable(); // this is order end_date when the order is made initially, IF the order is terminated (if is_terminated = 1) - 
                                                                                                                            // then it will keep its value of the order end_end date as it is = (original_end_date = the original order end_date value i.e [original_end_date = end_date] ) 
                                                                                                                            //             // the order end_date will have value of the termination date (end_date = order_termination_date)
                                                                                                                            // so this column i.e (original_end_date) keeps the original order end_date for us, when the order is terminated and end_date is altered

            $table->string('pr_status')->nullable(); // this is NULL when the order is made initially // this column is enum //

            $table->string('order_description')->nullable();


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
