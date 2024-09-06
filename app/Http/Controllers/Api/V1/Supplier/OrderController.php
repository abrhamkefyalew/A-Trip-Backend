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
use App\Http\Requests\Api\V1\SupplierRequests\StartOrderRequest;
use App\Http\Requests\Api\V1\SupplierRequests\AcceptOrderRequest;
use App\Http\Requests\Api\V1\SupplierRequests\UpdateOrderRequest;
use App\Http\Requests\Api\V1\SupplierRequests\CompleteOrderRequest;
use App\Http\Resources\Api\V1\OrderResources\OrderForSupplierResource;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * Display All Past Orders this Supplier is already involved with,
     *      THIS MEANS = the logged in Supplier can see a list of orders from orders table that have his supplier_id
     * 
     * this orders can be filtered with Order Status = ORDER_STATUS_PENDING, ORDER_STATUS_SET, ORDER_STATUS_START, ORDER_STATUS_COMPLETE
     * or all of them can be listed without filtering, depending on if the frontend passes the order_status_search parameter on the url
     * 
     */
    public function index(Request $request)
    {
        // get the logged in supplier identity
        $user = auth()->user();
        $supplier = Supplier::find($user->id);


        // list all the orders that the logged in supplier have involved with in the past
        $orders = Order::where('supplier_id', $supplier->id);

        

        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                if (!in_array($orderStatus, [Order::ORDER_STATUS_PENDING, Order::ORDER_STATUS_SET, Order::ORDER_STATUS_START, Order::ORDER_STATUS_COMPLETE])) {
                    return response()->json([
                        'message' => 'order_status_search should only be ' . Order::ORDER_STATUS_PENDING . ', ' . Order::ORDER_STATUS_SET . ', ' . Order::ORDER_STATUS_START . ', or ' . Order::ORDER_STATUS_COMPLETE
                    ], 422);
                }

                $orders = $orders->where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }

        }
        

        
        // check if the pagination works overall in this orders data list
        $ordersData = $orders->with('vehicleName', 'vehicle', 'driver', 'contractDetail')
            ->latest()
            ->paginate(FilteringService::getPaginate($request)); // get list of orders that fits this logged in supplier // get orders that the logged in supplier is already involved with in the past


        return OrderForSupplierResource::collection($ordersData);

    }


    /**
     * Display a listing of the resource.
     * 
     * Display PENDING Orders that can be accepted by this supplier
     * 
     * only PENDING orders
     */
    public function indexPending(Request $request)
    {
        // get the logged in supplier identity
        $user = auth()->user();
        $supplier = Supplier::find($user->id);


        // list all the vehicles of the logged in supplier
        $vehicles = Vehicle::where('supplier_id', $supplier->id)
            ->where('is_available', Vehicle::VEHICLE_AVAILABLE)
            ->where('with_driver', 0)
            ->latest()
            ->get();       // this get multiple vehicles of the supplier // which have with driver 0

        
        // from the vehicles of the logged in supplier lets get all the vehicle_name_ids separately
        // $vehicleNameIds = $vehicles->pluck('vehicle_name_id'); // if there are similar vehicle_name_id // this will contain them as duplicate // so we use the following, it will eliminate any duplicate vehicle_name_ids
        //
        // ABRHAM CHECK , SAMSON // here we must REMOVE the duplicated vehicle_name ids from the vehicle_name_id list // USING THE FOLLOWING
        // By chaining the unique method after pluck, I will get an array containing only unique vehicle_name_id values. This ensures that I do not have duplicates in the resulting list
        // i can utilize the unique method in Laravel collections. 
        $vehicleNameIds = $vehicles->pluck('vehicle_name_id')->unique();    
        

        $orders = Order::where('status', Order::ORDER_STATUS_PENDING);

        
        // this 0 value is = since supplier is trying to see orders. and supplier should only see orders which are with_drive = 0;  which means without driver
        // we are going to use the $withDriverValue value we set here in the following line to refer it inside the contract_details table
        $withDriverValue = 0;

        // this 1 value is = since supplier is trying to see orders. and supplier should only see orders that have "contract_details.is_available = 1", which means the contract is NOT terminated or expired. which makes the contract_detail to have is_available 1
        // we are going to use the $isAvailable value we set here in the following line to refer it inside the contract_details table
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

            if ($vehicle->supplier_id !== $supplier->id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or This Supplier does not have a vehicle with this id. Deceptive request Aborted.'], 403); 
            }

            if ($vehicle->vehicle_name_id !== $order->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 403); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 403); 
            }



            if ($supplier->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 403); 
            }
            if ($supplier->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
            }











            if ($order->status !== Order::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this order is not pending. it is already accepted , started or completed'], 403); 
            }

            if ($order->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 403); 
            }

            if ($order->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 403); 
            }
            
            if (($order->vehicle_id !== null) || ($order->driver_id !== null) || ($order->supplier_id !== null)) {
                return response()->json(['message' => 'this order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 403); 
            }


            // TODO
            // dd("order->contractDetail->with_driver" . $order->contractDetail->with_driver);

            if ($vehicle->with_driver !== $order->contractDetail->with_driver) {        // TEST IF THIS DOES WORK = $order->contractDetail->with_driver         // check abrham samson

                if (($vehicle->with_driver === 1) && ($order->contractDetail->with_driver === 0)) {
                    return response()->json(['message' => 'the order does not need a driver'], 403); 
                }
                else if (($vehicle->with_driver === 0) && ($order->contractDetail->with_driver === 1)) {
                    return response()->json(['message' => 'the order needs vehicle with a driver'], 403); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 403); 
                
            }

            // CHECK IF THE CONTRACT DETAIL IS NOT AVAILABLE
            if ($order->contractDetail->is_available !== 1) { // TEST IF THIS DOES WORK = $order->contractDetail->with_driver       // also test if this condition is needed   // check abrham samson
                return response()->json(['message' => 'this order contract_detail have is_available 0 currently for some reason, the contract_detail of this order should have is_available 1'], 403); 
            }

            // TODO check if the contract itself is expired or Terminated

            
            
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
    public function startOrder(StartOrderRequest $request, Order $order)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_ON_TRIP

    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_COMPLETE
     * 
     * when the vehicle is returned to the supplier
     */
    public function completeOrder(CompleteOrderRequest $request, Order $order)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_AVAILABLE

    }



    /**
     * Update the specified resource in storage.
     * 
     * SUPPLIER Should NOT UPDATE ORDER 
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
