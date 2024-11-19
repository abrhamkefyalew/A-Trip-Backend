<?php

use App\Models\InvoiceVehicle;
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
        Schema::create('invoice_vehicles', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('order_user_id')->nullable()->constrained('order_users');
            $table->foreignId('supplier_id')->constrained('suppliers'); // since an already consumed order may be deleted , this situation may make an already used vehicle order un-payable // this column ensures that an invoice is always related to supplier

            $table->uuid('transaction_id_system'); // this is our transaction id, which is created all the time // this should NOT be unique because when paying using invoice_code, all the invoices under that invoice code should have the same uuid (i.e. transaction_id_system)
            $table->string('transaction_id_banks')->nullable(); // this is the transaction id that comes from the banks during callback
            
            $table->date('start_date');
            $table->date('end_date');

            $table->integer('price_amount'); // is (the date differences multiplied by the order contract_detail price)
            $table->string('status')->default(InvoiceVehicle::INVOICE_STATUS_NOT_PAID); // this column is enum
            $table->date('paid_date')->nullable(); // initially it is NULL // set when system_admin or system owner pays this invoice

            $table->string('payment_method')->nullable(); // should be NULL initially

            //
            $table->json('request_payload')->nullable(); // if there is any request payload i need to store in the database // i will put it in this column
            

            // the columns that will be added below in the future here, are intended for the return data from the banks
            $table->json('response_payload')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_vehicles');
    }
};
