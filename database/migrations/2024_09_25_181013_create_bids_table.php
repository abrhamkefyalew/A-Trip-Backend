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
        Schema::create('bids', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->foreignId('order_id')->constrained('order_users');

            $table->foreignId('vehicle_id')->constrained('vehicles'); // when the bid is accepted by the customer i will get the supplier and the driver of this vehicle from vehicles table and put it in orders table with the vehicle_id

            $table->unique(['order_id', 'vehicle_id']);

            $table->integer('price_total'); // when the bid is accepted by individual customer              // this will go to order_users table column = price_total
            $table->integer('price_initial'); // when the bid is accepted by the individual customer        // this will go to invoice_users table column = price
            
            

            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
