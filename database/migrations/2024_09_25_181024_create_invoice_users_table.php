<?php

use App\Models\InvoiceUser;
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
        Schema::create('invoice_users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_user_id')->constrained('order_users');

            $table->uuid('transaction_id_system')->unique(); // this is our transaction id, which is created all the time // this should be UNIQUE always, because when always paying, each invoice MUST have Different uuid (i.e. transaction_id_system)
            $table->string('transaction_id_banks')->nullable(); // this is the transaction id that comes from the banks during callback

            // an invoice is paid two times ,         1. first initial payment (it could be 0 birr)           2. SECOND the Final payment (it is the left over price)
            

            $table->integer('price'); // this is the paid amount of an order, it could be the initial or the final payment of the order
            $table->string('status')->default(InvoiceUser::INVOICE_STATUS_NOT_PAID); // this column is enum
            $table->date('paid_date')->nullable(); // initially it is NULL // set when organization pays this invoice

            $table->string('payment_method'); // should not be null


            // 
            $table->json('request_payload')->nullable(); // if there is any request payload i need to store in the database // i will put it in this column

            
            // the columns that will be added below in the future here, are intended for the return data from the banks


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_users');
    }
};
