<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Vehicle;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = auth()->user();
        $supplier = Supplier::find($user->id);
        
        $vehicles = Vehicle::where('supplier_id', $supplier->id)->with('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple vehicles of the supplier

        return VehicleResource::collection($vehicles);
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
