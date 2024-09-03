<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\Api\V1\OrderResources\OrderForSupplierResource;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get the supplier identity
        $user = auth()->user();
        $supplier = Supplier::find($user->id);


        // list all the vehicles of the logged in supplier
        $vehicles = Vehicle::where('supplier_id', $supplier->id)
            ->where('is_available', Vehicle::VEHICLE_AVAILABLE)
            ->where('with_driver', 0)
            ->latest()
            ->get();       // this get multiple vehicles of the supplier

        
        // from the vehicles of the logged in supplier lets get all the vehicle_name_ids separately
        $vehicleNameIds = $vehicles->pluck('vehicle_name_id');  // ABRHAM CHECK , SAMSON // here we must REMOVE the duplicated vehicle_name ids from the vehicle_name_id list


        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                $orders = Order::where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }

        }
        else {
            $orders = Order::whereNotNull('id');
        }

        
        // this 0 value is = since supplier is trying to see orders. and supplier should only see orders which are with_drive = 0;  which means without driver
        // we are going to use the $withDriverValue value we set here in the following line
        $withDriverValue = 0;

        $isAvailable = 1;

        // 
        $ordersData = $orders->whereHas('contractDetail', function (Builder $builder) use ($withDriverValue, $isAvailable) {
            $builder->where('contract_details.with_driver', $withDriverValue)
                ->where('contract_details.is_available', $isAvailable);
        });


        // check if the pagination works overall in this orders data list
        $ordersDataFinal = $ordersData->whereIn('vehicle_name_id', $vehicleNameIds)
            ->where('is_terminated', 0)
            ->whereDate('end_date', '>=', today()->toDateString()) // toDateString() is used , to get and use only the date value of today(), // so the time value is stripped out
            ->with('vehicleName', 'vehicle', 'driver', 'contractDetail')
            ->latest()
            ->paginate(FilteringService::getPaginate($request)); // get list of orders that fits this logged in supplier


            return OrderForSupplierResource::collection($ordersDataFinal);

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
