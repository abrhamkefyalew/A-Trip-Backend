<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use App\Models\Contract;
use Illuminate\Http\Request;
use App\Models\ContractDetail;
use App\Models\OrganizationUser;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\ContractDetailResources\ContractDetailOrganizationResource;

class ContractDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * THIS LISTS VEHICLE_NAMES THAT CAN BE RENTED BY AN ORGANIZATION DEPENDING ON THE CONTRACT
     * it lists all CONTRACT_DETAILs with their VEHICLE_NAMEs for ALL CONTRACTs THAT ARE CURRENTLY VALID
     * 
     * THIS List is NOT for Single Contract.  the list is for ALL VALID CONTRACTs of that organization
     * 
     * 
     * in short when organization_user wants to make an order he sees the vehicle_names using this function
     * 
     */
    public function index(Request $request)
    {

        // any organizationUser with specified Organization id can see list of contract_details (i.e vehicle_names)
        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);
        
        $contracts = Contract::where('organization_id', $organizationUser->organization_id)
            ->where('terminated_date', null)
            ->whereDate('end_date', '>=', today()->toDateString()) // toDateString() is used , to get and use only the date value of today(), // so the time value is stripped out
            ->get();       // this get multiple contracts of the organization

        // Extract contract IDs from the $contracts collection
                // since it is a COLLECTION i can NOT get the contract_id like $contract->id.
                // $contract->id = this is for single model object.  does NOT work for a collection like in this scenario
                // Since $contract is a collection of contracts, i can't directly access the id attribute from the collection like i would with a single model object so i should do it like the following.
        $contractIds = $contracts->pluck('id');


        // check if the pagination works overall in this contract detail
        $contractDetails = ContractDetail::whereIn('contract_id', $contractIds)->where('is_available', 1)->with('vehicleName', 'contract')->latest()->paginate(FilteringService::getPaginate($request));
            
        return ContractDetailOrganizationResource::collection($contractDetails);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
