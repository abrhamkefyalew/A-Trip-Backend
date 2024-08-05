<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
            $uniqueCode = Str::random(8); // Adjust the length as needed

            // Check if the generated code already exists in the database
            while (Contract::where('contract_code', $uniqueCode)->exists()) {
                $uniqueCode = Str::random(8); // Regenerate the code if it already exists
            }
            // but in ANOTHER CLASS and Method, where we want to store a modified contract , 
                // by adding duplicated contract_code to signal that the contract is modified from its predecessor with similar contract code
                // we can add duplicated contract code. // because the columns have no unique attribute
                // since the column is not unique by nature, we can freely add a duplicated contract code 
            // BUT HERE, SINCE we are ADDING a NEW CONTRACT
                // any new contract should always have a unique contract code, // ensuring that a contract_code can only be duplicated if the contract is modified, by adding a new raw



            // Create the contract with the unique code
            $contract = Contract::create([
                'contract_code' => $uniqueCode,
                'organization_id' => $request['organization_id'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'is_active' => (int) $request->input('is_active', 1),
                'terminated_date' => $request['terminated_date'],
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
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        //
    }
}
