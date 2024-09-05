<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Api\V1\SupplierRequests\AcceptOrderRequest;
use App\Http\Requests\Api\V1\SupplierRequests\UpdateOrderRequest;
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
        $vehicleNameIds = $vehicles->pluck('vehicle_name_id');  
        //
        // ABRHAM CHECK , SAMSON // here we must REMOVE the duplicated vehicle_name ids from the vehicle_name_id list // USING THE FOLLOWING
        // By chaining the unique method after pluck, I will get an array containing only unique vehicle_name_id values. This ensures that I do not have duplicates in the resulting list
        // i can utilize the unique method in Laravel collections. 
        // $vehicleNameIds = $vehicles->pluck('vehicle_name_id')->unique();    
        

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
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * 
     * to Accept Order = ORDER_STATUS_SET
     */
    public function acceptOrder(AcceptOrderRequest $request, Order $order)
    {
        //
        $var = DB::transaction(function () use ($request, $order) {
            // get the supplier identity
            $user = auth()->user();
            $supplier = Supplier::find($user->id);


            $vehicle = Vehicle::find($request['vehicle_id']);

            if ($vehicle->supplier_id != $supplier->id ) {
                return response()->json(['message' => 'invalid Vehicle is selected. or This Supplier does not have a vehicle with this id. Deceptive request Aborted.'], 401); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 401); 
            }











            if ($order->status !== Order::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this order is not pending. it is already accepted , started or completed'], 401); 
            }

            if ($order->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 401); 
            }

            if ($order->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 401); 
            }
            
            if (($order->vehicle_id !== null) || ($order->driver_id !== null) || ($order->supplier_id !== null)) {
                return response()->json(['message' => 'this order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 401); 
            }


            if ($vehicle->with_driver !== $order->contractDetail->with_driver) {        // TEST IF THIS DOES WORK = $order->contractDetail->with_driver         // check abrham samson

                if (($vehicle->with_driver === 1) && ($order->contractDetail->with_driver === 0)) {
                    return response()->json(['message' => 'the order does not need a driver'], 401); 
                }
                else if (($vehicle->with_driver === 0) && ($order->contractDetail->with_driver === 1)) {
                    return response()->json(['message' => 'the order needs vehicle with a driver'], 401); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order.'], 401); 
                
            }

            // CHECK IF THE CONTRACT DETAIL IS NOT AVAILABLE
            if ($order->contractDetail->is_available !== 1) { // TEST IF THIS DOES WORK = $order->contractDetail->with_driver       // also test if this condition is needed   // check abrham samson
                return response()->json(['message' => 'this order contract_detail have is_available 0 currently for some reason, the contract_detail of this order should have is_available 1'], 401); 
            }

            // TODO check if the contract itself is expired

            
            
            $success = $order->update([
                'vehicle_id' => $request['vehicle_id'],
                'supplier_id' => $supplier->id,
                'status' => Order::ORDER_STATUS_SET,
            ]);

            
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderForSupplierResource::make($updatedOrder->load('vehicleName', 'vehicle', 'driver', 'contractDetail'));
                 
        });

        return $var;
    }


    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_START
     * 
     * when the vehicle departs from the supplier
     */
    public function startOrder(UpdateOrderRequest $request, Order $order)
    {


    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_COMPLETE
     * 
     * when the vehicle is returned to the supplier
     */
    public function completeOrder(UpdateOrderRequest $request, Order $order)
    {

    }



    /**
     * Update the specified resource in storage.
     * 
     * SUPPLIER DOES NOT UPDATE REQUEST 
     * 
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
