<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Contract;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreContractRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateContractRequest;
use App\Http\Resources\Api\V1\ContractResources\ContractResource;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Contract::class);

        // use Filtering service OR Scope to do this
        if ($request->has('organization_id_search')) {
            if (isset($request['organization_id_search'])) {
                $organizationId = $request['organization_id_search'];

                $contracts = Contract::where('organization_id', $organizationId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        else {
            $contracts = Contract::whereNotNull('id');
        }
        

        $contractData = $contracts->with('media')->latest()->paginate(FilteringService::getPaginate($request));

        return ContractResource::collection($contractData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContractRequest $request)
    {
        
        $var = DB::transaction(function () use ($request) {

            // Generate a unique random contract code
            $uniqueCode = Str::random(20); // Adjust the length as needed

            // Check if the generated code already exists in the database
            while (Contract::where('contract_code', $uniqueCode)->exists()) {
                $uniqueCode = Str::random(20); // Regenerate the code if it already exists
            }
            // HERE, SINCE we are ADDING a NEW CONTRACT
                // any new contract should always have a unique contract code, // ensuring that a contract_code can only be duplicated if the contract is modified, by adding a new raw
            // BUT in ANOTHER CLASS and Method, where we want to store a modified contract , 
                // by adding duplicated contract_code to signal that the contract is modified from its predecessor with similar contract code
                // we can add duplicated contract code. // because the columns have no unique attribute
                // since the column is not unique by nature, we can freely add a duplicated contract code 
            


            // todays date
            $today = Carbon::parse(today())->toDateString();
            

            // contract dates // from the request
            $contractRequestStartDate = Carbon::parse($request['start_date'])->toDateString();
            $contractRequestEndDate = Carbon::parse($request['end_date'])->toDateString();

            // request_start_date should be =< request_end_date - for contracts and orders
            if ($contractRequestStartDate > $contractRequestEndDate) {
                return response()->json(['message' => 'Contract Start Date should not be greater than the Contract End Date'], 400);
            }

            
            // contract end date = must be today or after today , (but end date can not be before today)
            // Check if end_date is greater than or equal to todays date
            if ($contractRequestEndDate < $today) {
                return response()->json(['message' => 'Contract End date must be greater than or equal to today\'s date.'], 400);
            }

            // contract start date can be anytime // since an already stated contract can be inserted in the system // ask samson



            // Create the contract with the unique code
            $contract = Contract::create([
                'contract_code' => $uniqueCode,
                'organization_id' => $request['organization_id'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'terminated_date' => null, // is NULL when the order is created initially // since we are creating this contract for the first time, it is not terminated yet
                            // if parent contract is Terminated (terminated_date=some_date)       // then we make all its child contract_details NOT Available by doing (is_available=0)
							// if parent contract is UnTerminated (terminated_date=NULL)           // then we make all its child contract_details  Re-Available by doing (is_available=1)
							// the "is_available" column in CONTRACT_DETAILs table should NOT be update separately,  // we ONLY update "is_available" when Terminating or UnTerminating the parent contract 

                'contract_name' => $request['contract_name'],
                'contract_description' => $request['contract_description'],
            ]);

            // CONTRACT MEDIA // PDF

            // since we are just storing a new contract so no need to delete the the others older contract files //
            // a contract files shall be removed ONLY when you update that contract
            // even when you modify a contract with another contract_id but similar contract_code you must NOT remove file, 
                // because the new contract modification is done in a new contract row and needs its own media, so there is no need to remove the older contract media

            // NO contract file remove, since it is the first time the contract is being stored
            // also use the MediaService class to remove file

            if ($request->has('organization_contract_file')) {
                $file = $request->file('organization_contract_file');
                $clearMedia = false; // or true // // NO organization_user image remove, since it is the first time the organization_user is being stored
                $collectionName = Contract::ORGANIZATION_CONTRACT_FILE;
                MediaService::storeFile($contract, $file, $clearMedia, $collectionName);
            }
            
            return ContractResource::make($contract->load('media', 'contractDetails', 'organization'));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Contract $contract)
    {
        // $this->authorize('view', $contract);
        
        return ContractResource::make($contract->load('media', 'contractDetails', 'organization'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContractRequest $request, Contract $contract)
    {
        //
        // contract_name, contract_description = we should only update these two // check abrham samson // ask samson
        // 
        //
        //
       
        $var = DB::transaction(function () use ($request, $contract) {
            
            $success = $contract->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }



            // MEDIA CODE SECTION
            // REMEMBER = (clearMedia) ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection // check abrham samson // remember
            //
            if ($request->has('organization_contract_file')) {
                $file = $request->file('organization_contract_file');
                $clearMedia = $request->input('organization_contract_file_remove', false);
                $collectionName = Contract::ORGANIZATION_CONTRACT_FILE;
                MediaService::storeFile($contract, $file, $clearMedia, $collectionName);
            }

            
            $updatedContract = Contract::find($contract->id);

            return ContractResource::make($updatedContract->load('media', 'contractDetails', 'organization'));

        });

        return $var;

    }


    /**
     * Update the specified resource in storage.
     */
    public function terminateContract(UpdateContractRequest $request, Contract $contract)
    {
        //
        //
        // 
        // terminated_date = for this we need end point
        //
        // $var = DB::transaction(function () {
        
        //      // NOTE : -  if we Terminate or UnTerminate a Contract here - then we should make all its child contract_details UnAvailable or Re-Available Respectively
        
        // });

        // return $var;
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        // there should not be contract delete
    }
}
