<?php

use App\Models\OrderUser;
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
        Schema::create('order_users', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('order_code'); // will be used if the a multiple vehicle order is made at once in one order request // so for them we use the same order code // it is not unique

            $table->foreignId('customer_id')->constrained('customers'); // if super_admin updates orders table , he should not update this organization_id column // updating it may create a problem // so updating this is not a good idea

            $table->foreignId('vehicle_name_id')->constrained('vehicle_names'); // this should NOT be null 

            // should nullable come before constrained or after // check first please
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles'); // this is NULL when the order is made initially
            $table->foreignId('driver_id')->nullable()->constrained('drivers'); // this is NULL when the order is made initially
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers'); // this is NULL when the order is made initially

            // these dates should NOT be null
            $table->date('start_date'); // is the date where the order is intended to start , - it is filled when the order is made       // if start_date is not mentioned OPTIONAL $table->date('start_date')->useCurrent(); OPTIONAL and could be used as default CHECK
            $table->date('begin_date')->nullable(); // is the date where the order is actually started ,  - it is filled when the vehicle reaches the customer (organization user)      // initially it is NULL when order is made  // set when order is STARTED
            $table->date('end_date'); // if the order is terminated , the order end_date should be assigned with the order termination date, and the original_end_date column will keep this original order end_date value 
                                                                                                                                                // (because original_end_date column had been assigned the order end_date when the order is created initially)
                                                                                                                                                        // as (original_end_date = the original order end_date value i.e [original_end_date = end_date] ) 
            

            $table->string('start_location')->nullable(); // should this be nullable // described by words
            $table->string('end_location')->nullable(); // should this be nullable // described by words

            // for latitude and longitude values, using DOUBLE data type generally a suitable choice // double is suitable for data types such as scientific calculations or geographical coordinates
            $table->double('start_latitude', 15, 10)->nullable();
            $table->double('start_longitude', 15, 10)->nullable();
            $table->double('end_latitude', 15, 10)->nullable();
            $table->double('end_longitude', 15, 10)->nullable();
            
            $table->string('status')->default(OrderUser::ORDER_STATUS_PENDING); // this column is enum //
            
            $table->boolean('is_terminated')->default(0);
            $table->date('original_end_date'); // this is order end_date when the order is made initially, IF the order is terminated (if is_terminated = 1) - 
                                                                                                                            // then it will keep its value of the order end_end date as it is = (original_end_date = the original order end_date value i.e [original_end_date = end_date] ) 
                                                                                                                            //             // the order end_date will have value of the termination date (end_date = order_termination_date)
                                                                                                                            // so this column i.e (original_end_date) keeps the original order end_date for us, when the order is terminated and end_date is altered

                                                                                                                            
            $table->integer('price_total')->nullable(); // initially this is NULL // it will be inserted when a bid is selected by the customer
            $table->boolean('payed_complete_status')->default(0); // this will be 1 when both invoices of the order are paid  // it is 0 even if the initial amount is paid

            $table->longText('order_description')->nullable();

            $table->boolean('with_driver')->default(0);
            $table->boolean('with_fuel')->default(0);
            $table->boolean('periodic')->default(0);


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_users');
    }
};
