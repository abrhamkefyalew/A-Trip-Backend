<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
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
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', ContractDetail::class);

        // use Filtering service OR Scope to do this
        if ($request->has('contract_id_search')) {
            if (isset($request['contract_id_search'])) {
                $contractId = $request['contract_id_search'];

                $contractDetail = ContractDetail::where('contract_id', $contractId);
            } else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
            
        }
        else {
            $contractDetail = ContractDetail::whereNotNull('id');
        }
        

        $contractDetailData = $contractDetail->latest()->paginate(FilteringService::getPaginate($request));

        return ContractDetailResource::collection($contractDetailData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContractDetailRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            $validatedData = $request->validated();
            
            $contractDetail = ContractDetail::create([
                'contract_id' => $validatedData['contract_id'],
                'vehicle_name_id' => $validatedData['vehicle_name_id'],
                'with_driver' => (int) $validatedData->input('with_driver', 0),         // // do we need this // because we already have it in orders ? , or should we use it here and remove it in orders
                'with_fuel' => (int) $validatedData->input('with_fuel', 0),
                'price' => $validatedData['price'],
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
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContractDetail $contractDetail)
    {
        //
    }
}
