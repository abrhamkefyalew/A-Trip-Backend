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
        Schema::create('constants', function (Blueprint $table) {
            $table->id()->from(10000);
            
            $table->string('title')->nullable()->unique(); // this column must always be constant value from Constant Model (i.e Constant::ORDER_USER_INITIAL_PAYMENT_PERCENT)
            $table->integer('percent_value')->default(25); // this column holds the percent value of that constant table 
                                                           // these can not be 0 for the moment, it means there must always be initial payment 
                                                           // the default value of this column is set to 25 (i.e 25 %)
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constants');
    }
};
