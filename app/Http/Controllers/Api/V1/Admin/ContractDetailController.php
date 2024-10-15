<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Order;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ContractDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreContractDetailRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateContractDetailRequest;
use App\Http\Resources\Api\V1\ContractDetailResources\ContractDetailResource;

class ContractDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * in the below request, either he must send contract_id or organization_id
     * 
     * when the SUPER_ADMIN wants to make an order in behalf of an organization (i.e via call center), 
     * he will use the following index method by passing organization_id , to list eligible contract details (with their corresponding vehicle_names)
     * then he will select one of the contract details and make the order for the requester organization
     * 
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', ContractDetail::class);

        // use Filtering service OR Scope to do this

        if ($request->has('contract_id_search')) {
            // he is seeing the contract // so knows IF the contract is terminated or not beforehand
            if (isset($request['contract_id_search'])) {
                $contractId = $request['contract_id_search'];

                $contractDetails = ContractDetail::where('contract_id', $contractId);
            } else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
            
        }
        else {
            /**
             * This ELSE Condition Does the following
             * 
             * THIS LISTS VEHICLE_NAMES THAT CAN BE RENTED BY AN ORGANIZATION DEPENDING ON THE CONTRACT
             * it lists all CONTRACT_DETAILs with their VEHICLE_NAMEs for ALL CONTRACTs THAT ARE CURRENTLY VALID
             * 
             * THIS List is NOT for Single Contract.  the list is for ALL VALID CONTRACTs of that organization
             * 
             * 
             * the super_admin then will choose one of the vehicle_names and make an order
             * 
             * 
             * in short when super_user wants to make an order for an organization he sees the vehicle_names using this function
             * 
             * 
             * NOTE: - THIS METHOD SHOULD ONLY BE USED WHEN ADMIN IS MAKING ORDER FOR ORGANIZATIONS.    -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   - samson, abrham = you MUST READ THIS
             *          Because it ONLY shows the CONTRACT_DETAILs that are Active and Eligible
             *              i.e = WITH    
             *                      "contract.terminated_date = null"       &       "contract.end_date >= today()"  
             *                      "contract_detail.is_available = 1" 
             * 
             */
            if (! $request->has('organization_id')) {
                return response()->json(['message' => 'must send organization id.'], 404); 
            }
            if (! isset($request['organization_id'])) { 
                return response()->json(['message' => 'must set organization id.'], 404); 
            }

            $contracts = Contract::where('organization_id', $request['organization_id'])
                ->where('terminated_date', null)
                ->whereDate('end_date', '>=', today()->toDateString()) // toDateString() is used , to get and use only the date value of today(), // so the time value is stripped out
                ->get();       // this get multiple contracts of the organization

            // Extract contract IDs from the $contracts collection
                    // since it is a COLLECTION i can NOT get the contract_id like $contract->id.
                    // $contract->id = this is for single model object.  does NOT work for a collection like in this scenario
                    // Since $contract is a collection of contracts, i can't directly access the id attribute from the collection like i would with a single model object so i should do it like the following.
            $contractIds = $contracts->pluck('id');


            // check if the pagination works overall in this contract detail
            $contractDetails = ContractDetail::whereIn('contract_id', $contractIds)->where('is_available', 1);
        }
        

        $contractDetailData = $contractDetails->with('vehicleName', 'contract')->latest()->paginate(FilteringService::getPaginate($request));

        return ContractDetailResource::collection($contractDetailData);
    }






    

    // if anybody wants all ContractDetails list
    // i will send the Contract as relation with the ContractDetail, because there is no contract end_date in ContractDetail Table, 
    // but we can find a contract end_date from Contracts Table  // these way we can know the contract expiration from contracts table






    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContractDetailRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            // $validatedData = $request->validated();


            if ($request['with_fuel'] == 1 && $request['with_driver'] == 0) {
                return response()->json(['message' => 'since you set with_fuel = 1, with_driver must also be 1. a Contract Detail that require fuel must also require driver'], 400);
            }

            
            $contractDetail = ContractDetail::create([
                'contract_id' => $request['contract_id'],
                'vehicle_name_id' => $request['vehicle_name_id'],
                'with_driver' => (int) $request->input('with_driver', 0),
                'with_fuel' => (int) $request->input('with_fuel', 0),
                'periodic' => (int) $request->input('periodic', 0),
                'price_contract' => $request['price_contract'],
                'price_vehicle_payment' => $request['price_vehicle_payment'],
                'tax' => (int) $request->input('tax', ContractDetail::CONTRACT_DETAIL_DEFAULT_TAX_15), // use isset() or filled() on this one
                'is_available' => 1, // is_available should be not be sent at ContractDetail store for the first time, it is "1" by default in the controller       // 1 = means parent contract not terminated   // 0 = means parent contract terminated
                            // the "is_available" column in CONTRACT_DETAILs table should NOT be update separately,  // we ONLY update "is_available" when Terminating or UnTerminating the PARENT CONTRACT
							// if parent contract is Terminated (terminated_date=some_date)       // then we make all its child contract_details NOT Available by doing (is_available=0) 
							// if parent contract is UnTerminated (terminated_date=NULL)           // then we make all its child contract_details  Re-Available by doing (is_available=1)
							// 1 = means parent contract NOT terminated   // 0 = means parent contract terminated
            ]);


            return ContractDetailResource::make($contractDetail->load('contract', 'vehicleName'));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(ContractDetail $contractDetail)
    {
        // $this->authorize('view', $contractDetail);

        return ContractDetailResource::make($contractDetail->load('contract', 'vehicleName'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContractDetailRequest $request, ContractDetail $contractDetail)
    {
        
        $var = DB::transaction(function () use ($request, $contractDetail) {
            
           // NOTE : - // the "is_available" column in CONTRACT_DETAILs table should NOT be update separately,  // we ONLY update "is_available" when Terminating or UnTerminating the PARENT CONTRACT


           if (Order::where('contract_detail_id', $contractDetail->id)->exists()) {
                return response()->json(['message' => 'Cannot Update the Contract Detail because it is in use by organization Orders.'], 409);
            }

        


           $success = $contractDetail->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }


            $updatedContractDetail = ContractDetail::find($contractDetail->id);

            return ContractDetailResource::make($updatedContractDetail->load('contract', 'vehicleName'));
            
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContractDetail $contractDetail)
    {
        // $this->authorize('delete', $contractDetail);

        $var = DB::transaction(function () use ($contractDetail) {

            if (Order::where('contract_detail_id', $contractDetail->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the Contract Detail because it is in use by organization Orders.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the Contract Detail because it is in use by organization Orders.'
                ], Response::HTTP_CONFLICT);
            }

            $contractDetail->delete();

            return response()->json(true, 200);

        });

        return $var;
    }


    public function restore(string $id)
    {
        $contractDetail = ContractDetail::withTrashed()->find($id);

        // $this->authorize('restore', $contractDetail);

        $var = DB::transaction(function () use ($contractDetail) {
            
            if (!$contractDetail) {
                abort(404);    
            }
    
            $contractDetail->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }


}
