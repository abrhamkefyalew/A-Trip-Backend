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
        Schema::create('contract_details', function (Blueprint $table) {
            $table->id()->from(100000);

            $table->foreignId('contract_id')->constrained('contracts');

            // the below two columns are mutually exclusive // if one is filled the other should be null
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types'); // should nullable precede constrained // check first // correct all three nullable foreign ids below 
            $table->foreignId('vehicle_name_id')->nullable()->constrained('vehicle_names');

            $table->boolean('with_driver');
            $table->boolean('with_fuel');
            
            $table->boolean('is_terminated')->default(0);
            $table->timestamp('original_end_date'); 

            // the PDF or JPG media for this contract will be in medias table
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_details');
    }
};
