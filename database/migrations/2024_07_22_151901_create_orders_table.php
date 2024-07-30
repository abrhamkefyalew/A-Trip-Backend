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

            $table->string('order_code'); // will be used if the a multiple vehicle order is made // so for them we use the same order code // it is not unique

            $table->foreignId('organization_id')->constrained('organizations');

            $table->foreignId('vehicle_name_id')->constrained('vehicle_names'); // this should NOT be null

            // should nullable come before constrained or after // check first please
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles'); // this is NULL when the order is made initially
            $table->foreignId('driver_id')->nullable()->constrained('drivers'); // this is NULL when the order is made initially

            $table->timestamp('start_date');    // if start_date is not mentioned OPTIONAL  // $table->timestamp('start_date')->useCurrent(); OPTIONAL // is the OPTIONAL code insert it as default value CHECK
            $table->timestamp('end_date'); // if the order is terminated , the order end_date will be assigned with the order termination date, and the original end date will be assigned in the column = original_end_date
            $table->string('status')->default(Order::ORDER_STATUS_PENDING); // this column is enum //
            
            $table->boolean('is_terminated')->default(0);
            $table->timestamp('original_end_date')->nullable(); // this is originally NULL , IF the order is terminated (if is_terminated = 1) - then it will be assigned the order end_end date = (the original order end_date) 

            $table->string('pr_status')->nullable(); // this column is enum //


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
