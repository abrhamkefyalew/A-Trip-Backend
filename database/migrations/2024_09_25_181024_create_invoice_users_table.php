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

            $table->foreignId('order_id')->constrained('order_users');

            // an invoice is payed two times , first initial payment (it could be 0 birr)     SECOND the Final payment (it is the left over pricet )
            $table->integer('price'); // this is the paid amount of an order, it could be the initial or the final payment of the order
            $table->string('status')->default(InvoiceUser::INVOICE_STATUS_NOT_PAID); // this column is enum
            $table->date('paid_date')->nullable(); // initially it is NULL // set when organization pays this invoice

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
