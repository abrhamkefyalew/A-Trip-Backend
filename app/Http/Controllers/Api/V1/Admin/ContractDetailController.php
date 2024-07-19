<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\ContractDetail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreContractDetailRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateContractDetailRequest;

class ContractDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContractDetailRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(ContractDetail $contractDetail)
    {
        //
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
