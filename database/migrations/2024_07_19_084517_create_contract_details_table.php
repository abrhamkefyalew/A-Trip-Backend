<?php

use App\Models\ContractDetail;
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
        Schema::create('contract_details', function (Blueprint $table) {
            $table->id()->from(100000);

            $table->foreignId('contract_id')->constrained('contracts');

            $table->foreignId('vehicle_name_id')->constrained('vehicle_names'); // this should NOT be null

            $table->boolean('with_driver')->default(0);
            $table->boolean('with_fuel')->default(0);
            $table->boolean('periodic')->default(0);

            $table->decimal('price_contract', 10, 2); // the contract winning price                                                                            // the driver or vehicle_supplier should not see this price
            $table->decimal('price_vehicle_payment', 10, 2); // the price that is to be paid for vehicle_supplier or driver (i.e payed to the vehicle account) // the organization should not see this price

            $table->decimal('tax', 4, 2)->default(ContractDetail::CONTRACT_DETAIL_DEFAULT_TAX_15);

            $table->boolean('is_available')->default(1);  
                                            // the "is_available" column in CONTRACT_DETAILs table should NOT be update separately,  // we ONLY update "is_available" when Terminating or UnTerminating the PARENT CONTRACT                                                     
							                // if parent contract is Terminated (terminated_date=some_date)       // then we make all its child contract_details NOT Available by doing (is_available=0) 
							                // if parent contract is UnTerminated (terminated_date=NULL)           // then we make all its child contract_details  Re-Available by doing (is_available=1)
							                // 1 = means parent contract NOT terminated   // 0 = means parent contract terminated

            
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
