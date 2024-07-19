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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id()->from(10000);

            $table->string('contract_code'); // will be used if the same contract is modified and continued after termination // so for them we use the same contract code
            $table->foreignId('organization_id')->constrained('organizations');
            $table->timestamp('start_date');    // if start_date is not mentioned OPTIONAL  // $table->timestamp('start_date')->useCurrent(); OPTIONAL
            $table->timestamp('end_date');
            $table->boolean('is_active')->default(1);
            
            $table->boolean('is_terminated')->default(0);
            $table->timestamp('original_end_date'); 

            //// the PDF or JPG media for this contract will be in medias table
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
