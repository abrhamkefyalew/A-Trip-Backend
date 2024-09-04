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

            $table->string('contract_code'); // will be used if the same contract is modified and continued after termination // so for them we use the same contract code // it is not unique
            $table->foreignId('organization_id')->constrained('organizations');

            $table->date('start_date');    // if start_date is not mentioned OPTIONAL  // $table->date('start_date')->useCurrent(); OPTIONAL // is the OPTIONAL code insert it as default value CHECK
            $table->date('end_date'); 

            $table->date('terminated_date')->nullable();  
                                                // if parent contract is Terminated (terminated_date=some_date)       // then we make all its child contract_details NOT Available by doing (is_available=0)
							                    // if parent contract is UnTerminated (terminated_date=NULL)           // then we make all its child contract_details  Re-Available by doing (is_available=1)
							                    // the "is_available" column in CONTRACT_DETAILs table should NOT be update separately,  // we ONLY update "is_available" when Terminating or UnTerminating the parent contract 
            
            $table->string('contract_name');
            $table->longText('contract_description')->nullable();



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
