<?php

use App\Models\Invoice;
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_code'); // Essential if the a multiple invoices (order PRs [Payment Requests]) is made at once for multiple orders which need to be considered at once // so for them we use the same invoice code // it is not unique

            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('organization_id')->constrained('organizations'); // since an already consumed order may be deleted , this situation may make an already used vehicle order un-payable // this column ensures that an invoice is always related to organization

            $table->uuid('transaction_id_system'); // this is our transaction id, which is created all the time // this should NOT be unique because when paying using invoice_code, all the invoices under that invoice code should have the same uuid (i.e. transaction_id_system)
            $table->string('transaction_id_banks')->nullable(); // this is the transaction id that comes from the banks during callback
            
            $table->date('start_date');
            $table->date('end_date');

            $table->integer('price_amount'); // is (the date differences multiplied by the order contract_detail price)
            $table->string('status')->default(Invoice::INVOICE_STATUS_NOT_PAID); // this column is enum
            $table->date('paid_date')->nullable(); // initially it is NULL // set when organization pays this invoice

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
        Schema::dropIfExists('invoices');
    }
};
