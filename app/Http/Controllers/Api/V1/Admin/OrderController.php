<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ContractDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\OrderResources\OrderResource;
use App\Http\Requests\Api\V1\AdminRequests\StartOrderRequest;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrderRequest;
use App\Http\Requests\Api\V1\AdminRequests\AcceptOrderRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrderRequest;
use App\Http\Requests\Api\V1\AdminRequests\CompleteOrderRequest;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Order::class);

        $orders = Order::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('organization_id_search')) {
            if (isset($request['organization_id_search'])) {
                $organizationId = $request['organization_id_search'];

                $orders = $orders->where('organization_id', $organizationId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('is_terminated_search')) {
            if (isset($request['is_terminated_search'])) {
                $isTerminated = $request['is_terminated_search'];

                $orders = $orders->where('is_terminated', $isTerminated);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
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


        $ordersData = $orders->with('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of the organization

        return OrderResource::collection($ordersData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            if ($request->has('orders')) {
                
                $orderIds = [];
                    // since multiple orders can be sent at once 
                        // i will put similar order_code in OrderController = for those multiple orders that are sent at once
                        //
                // Generate a random order code
                $uniqueCode = Str::random(20); // Adjust the length as needed

                // Check if the generated code already exists in the database
                while (Order::where('order_code', $uniqueCode)->exists()) {
                    $uniqueCode = Str::random(20); // Regenerate the code if it already exists
                }

                
                // i think this two if conditions are checked and validated by the FormRequest=StoreOrderRequest // so it may be duplicate // but check first
                if (! $request->has('organization_id')) {
                    return response()->json(['message' => 'must send organization id.'], 404); 
                }
                if (! isset($request['organization_id'])) { 
                    return response()->json(['message' => 'must set organization id.'], 404); 
                }



                // Now do operations on each of the orders sent
                foreach ($request->safe()->orders as $requestData) {

                    // this contract_detail_id should be owned by the organization that the super_admin is making the order to
                    $contractDetail = ContractDetail::where('id', $requestData['contract_detail_id'])->first();
                    $contract = Contract::where('id', $contractDetail->contract_id)->first();

                    if (!$contract) {
                        // contract not found
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Vehicle Name does NOT exist.'], 404); 
                    }

                    if ($request['organization_id'] != $contract->organization_id) {
                        return response()->json(['message' => 'The sent organization_id is NOT equal to the contract\'s organization_id for the selected vehicle_name. invalid Vehicle Name is selected for the Order. Deceptive request Aborted.'], 401); 
                    }
                    if ($contractDetail->is_available != 1) {
                        // the parent contract of this contract_detail is Terminated
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. The Contract Detail for this Vehicle Name is NOT Available, because the Contract for the requested Vehicle Name is Terminated.'], 404);
                    }
                    if ($contract->terminated_date !== null) {
                        // Contract is terminated
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Vehicle Name is Terminated.'], 404); 
                    }
                    

                    // CHECK REQUEST DATEs (Order dates)

                    // FIRST OF ALL = Check if start_date and end_date are valid dates
                    if (!strtotime($requestData['start_date']) || !strtotime($requestData['end_date'])) {
                        return response()->json(['message' => 'Invalid date format.'], 400);
                    }



                    // order dates // from the request
                    $orderRequestStartDate = Carbon::parse($requestData['start_date'])->toDateString();
                    $orderRequestEndDate = Carbon::parse($requestData['end_date'])->toDateString();
                    // contract dates // from contracts table in the database
                    $contractStartDate = Carbon::parse($contract->start_date)->toDateString();
                    $contractEndDate = Carbon::parse($contract->end_date)->toDateString();

                    // todays date  // it should be moved out of the foreach loop // check abrham samson
                    $today = now()->format('Y-m-d');

                    /* 
                        // LOG  -  TEST - - - Remove this
                            // used to check that = order start_date can not be before the contract starting date ,     but order start_data can be on the day of contract starting date and after
                                $aa = $orderRequestStartDate < $contractStartDate;
                                dd($orderRequestStartDate . " < " . $contractStartDate . " = " . ($aa ? 'true' : 'false'));

                        // OUTPUT should be   -   -   -   -   - // it should output the following
                                // "2024-12-27 < 2024-12-27 = false"
                    */

                    // check if the contract for the selected vehicle_name is NOT expired 
                    // the contract actual end_date = must be today or in the days after today 
                    // contract end_date - should be greater than or equals to today
                    if ($contractEndDate < $today) {
                        return response()->json(['message' => 'the Contract for the selected vehicle_name is Expired, Contract End date must be greater than or equal to today\'s date.'], 400);
                    }
                    

                    
                    // order start date = must be today or in the days after today , (but start date can not be before today)
                    // Check if start_date is greater than or equal to today's date
                    if ($orderRequestStartDate < $today) {
                        return response()->json(['message' => 'Order Start date must be greater than or equal to today\'s date.'], 400);
                    }
                    // order end date = must be today or in the days after today , (but end date can not be before today)
                    // Check if end_date is greater than or equal to today's date
                    if ($orderRequestEndDate < $today) {
                        return response()->json(['message' => 'Order End date must be greater than or equal to today\'s date.'], 400);
                    }

                
                    if ($orderRequestStartDate < $contractStartDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order start_date can not be before the contract starting date'], 400);
                    }
                    if ($orderRequestStartDate > $contractEndDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order start_date can not be after the contract expiration date'], 400);
                    }
                    if ($orderRequestEndDate < $contractStartDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order end_date can not be before the contract starting date'], 400);
                    }
                    if ($orderRequestEndDate > $contractEndDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order end_date can not be after the contract expiration date'], 400);
                    }

                    
                    // request_start_date should be =< request_end_date - for contracts and orders
                    if ($orderRequestStartDate > $orderRequestEndDate) {
                        return response()->json(['message' => 'Order Start Date should not be greater than the Order End Date'], 400);
                    }



                    $order = Order::create([
                        'order_code' => $uniqueCode,

                        'organization_id' => $request['organization_id'],
                        'contract_detail_id' => $requestData['contract_detail_id'],
                        
                        'vehicle_name_id' => $contractDetail->vehicle_name_id,

                        'vehicle_id' => null,   // is NULL when the order is created initially
                        'driver_id' => null,    // is NULL when the order is created initially
                        'supplier_id' => null,    // is NULL when the order is created initially

                        'start_date' => $requestData['start_date'],
                        'begin_date' => null,                           // is NULL when the order is created initially, // and set when the order is started
                        'end_date' => $requestData['end_date'],

                        'start_location' => $requestData['start_location'],
                        'end_location' => $requestData['end_location'],

                        'start_latitude' => $requestData['start_latitude'],
                        'start_longitude' => $requestData['start_longitude'],
                        'end_latitude' => $requestData['end_latitude'],
                        'end_longitude' => $requestData['end_longitude'],

                        'status' => Order::ORDER_STATUS_PENDING,    // is PENDING when order is created initially

                        'is_terminated' => 0,   // is 0 (false) when order created is initially
                        'original_end_date' => $requestData['end_date'], // this always holds the end_date of the order as backup, incase the order is terminated.   
                                                                        // if the order is terminated the end_date will be assigned the termination_date.      // So (original_end_date) holds the original order (end_date) as backup 
        
                        'pr_status' => null,    // is NULL when the order is created initially

                        'order_description' => $requestData['order_description'],                                                                                   
                    ]);

                    $orderIds[] = $order->id;
                    

                    
                }

                // WORKS
                $orders = Order::whereIn('id', $orderIds)->with('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail')->latest()->paginate(FilteringService::getPaginate($request));       // this get the orders created here
                return OrderResource::collection($orders);
            
            }
            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        // $this->authorize('view', $order);
        
        return OrderResource::make($order->load('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail'));
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

            $vehicle = Vehicle::find($request['vehicle_id']);
            $supplier = Supplier::find($vehicle->supplier_id);  // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            $driver = Driver::find($vehicle->driver_id);        // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            
            if ($vehicle->vehicle_name_id !== $order->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 401); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 401); 
            }


            // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            if ($driver) {
                if ($driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 403); 
                }
                if ($driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 403); 
                }
            }
            // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            if ($supplier) {
                if ($supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 403); 
                }
                if ($supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
                }
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
                'driver_id' => $vehicle->driver_id,         // handle if $vehicle->driver_id becomes NULL
                'supplier_id' => $vehicle->supplier_id,     // handle if $vehicle->supplier_id becomes NULL
                'status' => Order::ORDER_STATUS_SET,
            ]);
            //            
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'contractDetail', 'organization'));
                 
        });

        return $var;
    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_START
     * 
     * 
     */
    public function startOrder(StartOrderRequest $request, Order $order)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_ON_TRIP       - ALSO order begin_date will be set to today()      - ALSO order status will be ORDER_STATUS_START

        $var = DB::transaction(function () use ($request, $order) {

            if ($order->driver) {
                if ($order->driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 403); 
                }
                if ($order->driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 403); 
                }
            }
            if ($order->supplier) {
                if ($order->supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 403); 
                }
                if ($order->supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
                }
            }


            if ($order->status !== Order::ORDER_STATUS_SET) {
                return response()->json(['message' => 'this order is not SET (ACCEPTED). order should be SET (ACCEPTED) before it can be STARTED.'], 403); 
            }

            if ($order->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 403); 
            }

            if ($order->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 403); 
            }


            // CHECK IF THE CONTRACT DETAIL IS NOT AVAILABLE
            if ($order->contractDetail->is_available !== 1) { // TEST IF THIS DOES WORK = $order->contractDetail->with_driver       // also test if this condition is needed   // check abrham samson
                return response()->json(['message' => 'this order contract_detail have is_available 0 currently for some reason, the contract_detail of this order should have is_available 1'], 403); 
            }

            // TODO check if the contract itself is expired or Terminated




            $success = $order->update([
                'status' => Order::ORDER_STATUS_START,
                'begin_date' => today()->toDateString(),
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 422);
            }

            $vehicle = Vehicle::find($order->vehicle_id);
            //
            if (!$vehicle) {
                return response()->json(['message' => 'we could not find the actual vehicle of the vehicle_id in this order'], 404);
            }

            $successTwo = $vehicle->update([
                'is_available' => Vehicle::VEHICLE_ON_TRIP,
            ]);
            //
            if (!$successTwo) {
                return response()->json(['message' => 'Vehicle Update Failed'], 422);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'contractDetail', 'organization'));

        });

        return $var;

    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_COMPLETE
     * 
     * 
     */
    public function completeOrder(CompleteOrderRequest $request, Order $order)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_AVAILABLE

        $var = DB::transaction(function () use ($request, $order) {

            if ($order->status !== Order::ORDER_STATUS_START) {
                return response()->json(['message' => 'this order is not STARTED. order should be STARTED before it can be COMPLETED.'], 403); 
            }


            $success = $order->update([
                'status' => Order::ORDER_STATUS_COMPLETE,
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 422);
            }

            $vehicle = Vehicle::find($order->vehicle_id);
            //
            if (!$vehicle) {
                return response()->json(['message' => 'we could not find the actual vehicle of the vehicle_id in this order'], 404);
            }

            $successTwo = $vehicle->update([
                'is_available' => Vehicle::VEHICLE_AVAILABLE,
            ]);
            //
            if (!$successTwo) {
                return response()->json(['message' => 'Vehicle Update Failed'], 422);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'contractDetail', 'organization'));

        });

        return $var;

    }












    /**
     * Update the specified resource in storage.
     * 
     * NOT RECOMMENDED, 
     * 
     * SHOULD BE TESTED in every way to ensure that it does not cause inconsistency and error
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
        $var = DB::transaction(function () use ($request, $order) {

            $vehicle = Vehicle::find($request['vehicle_id']);
            $supplier = Supplier::find($vehicle->supplier_id);  // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            $driver = Driver::find($vehicle->driver_id);        // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson

            if ($vehicle->vehicle_name_id !== $order->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 401); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 401); 
            }


            // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            if ($driver) {
                if ($driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 403); 
                }
                if ($driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 403); 
                }
            }
            // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            if ($supplier) {
                if ($supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 403); 
                }
                if ($supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
                }
            }



            // this contract_detail_id should be owned by the organization that the super_admin is making the order to
            if ($request->has('contract_detail_id') && isset($request['contract_detail_id'])) {
                $contractDetail = ContractDetail::where('id', $request['contract_detail_id'])->first();

                if ($contractDetail) {
                    if ($contractDetail->is_available != 1) {
                        // the parent contract of this contract_detail is Terminated
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. The Contract Detail for this Vehicle Name is NOT Available, because the Contract for the requested Vehicle Name is Terminated.'], 404);
                    }

                    if ($vehicle->vehicle_name_id !== $contractDetail->vehicle_name_id) {
                        return response()->json(['message' => 'the vehicle\'s vehicle_name_id is NOT equal to contractDetail\'s vehicle_name_id. Deceptive request Aborted.'], 401); 
                    }

                    $contract = Contract::where('id', $contractDetail->contract_id)->first();

                    if ($contract) {
                        if ($contract->terminated_date !== null) {
                            // Contract is terminated
                            return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Vehicle Name is Terminated.'], 404); 
                        }

                        if ($request->has('organization_id') && isset($request['organization_id'])) {
                            if ($request['organization_id'] != $contract->organization_id) {
                                return response()->json(['message' => 'The sent organization_id is NOT equal to the contract\'s organization_id for the selected vehicle_name. invalid Vehicle Name is selected for the Order. Deceptive request Aborted.'], 401); 
                            }
                            
                        }
                    }
                    else {
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the contract_detail received from the request does not have Contract (Contract could not be found for the sent contract_detail in the request)'], 404); 
                    }

                } 
                else {
                    return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the contract_detail received from the request does NOT exist'], 404); 
                }
            }

            
            


            
            
            
            // CHECK REQUEST DATEs (Order dates)
            // todays date
            $today = now()->format('Y-m-d');

            if ($contract) {
                $contractStartDate = Carbon::parse($contract->start_date)->toDateString();
                $contractEndDate = Carbon::parse($contract->end_date)->toDateString();
            }

            // START DATE of order
            if ($request->has('start_date') && isset($request['start_date'])) {

                if (!strtotime($request['start_date'])) {
                    return response()->json(['message' => 'Invalid date format.'], 400);
                }
                $orderRequestStartDate = Carbon::parse($request['start_date'])->toDateString();
                

                            
                // order start date = must be today or in the days after today , (but start date can not be before today)
                // Check if start_date is greater than or equal to today's date
                if ($orderRequestStartDate < $today) {
                    return response()->json(['message' => 'Order Start date must be greater than or equal to today\'s date.'], 400);
                }

                if ($contract) {
                    if ($orderRequestStartDate < $contractStartDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order start_date can not be before the contract starting date'], 400);
                    }
                    if ($orderRequestStartDate > $contractEndDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order start_date can not be after the contract expiration date'], 400);
                    }
                }

            }

            // END DATE of order
            if ($request->has('end_date') && isset($request['end_date'])) {

                if (!strtotime($request['end_date'])) {
                    return response()->json(['message' => 'Invalid date format.'], 400);
                }
                $orderRequestEndDate = Carbon::parse($request['end_date'])->toDateString();
                

                // order end date = must be today or in the days after today , (but end date can not be before today)
                // Check if end_date is greater than or equal to today's date
                if ($orderRequestEndDate < $today) {
                    return response()->json(['message' => 'Order End date must be greater than or equal to today\'s date.'], 400);
                }

                if ($contract) {
                    if ($orderRequestEndDate < $contractStartDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order end_date can not be before the contract starting date'], 400);
                    }
                    if ($orderRequestEndDate > $contractEndDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order end_date can not be after the contract expiration date'], 400);
                    }
                }
                
            }
            
            
            if ( ($request->has('start_date') && isset($request['start_date'])) && ($request->has('end_date') && isset($request['end_date'])) ) {
                // request_start_date should be =< request_end_date - for contracts and orders
                if ($orderRequestStartDate > $orderRequestEndDate) {
                    return response()->json(['message' => 'Order Start Date should not be greater than the Order End Date'], 400);
                }
            }







            // DO THE ACTUAL UPDATE on Orders Table
            $success = $order->update($request->validated());


            if (isset($request['vehicle_id'])) {
                $order->vehicle_id = $request['vehicle_id']; // this is duplicate , since the $success = $order->update($request->validated()); will have updated it , since it will find vehicle_id in $request->validated()
                $order->driver_id = $vehicle->driver_id;
                $order->supplier_id = $vehicle->supplier_id;
            }
            if ($request->has('is_terminated') && $request['is_terminated'] == 1) {
                $order->end_date = today()->toDateString();
            }
            if ($request->has('contract_detail_id') && isset($request['contract_detail_id'])) {
                // should the contract of the contract_detail be checked also // check abrham samson
                $order->vehicle_name_id = $contractDetail->vehicle_name_id;
                
            }

            $order->save();


            if ($request->has('country') || $request->has('city')) {
                if ($order->address) {
                    $order->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $order->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }


            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }

            $updatedOrder = Order::find($order->id);


            return OrderResource::make($updatedOrder->load('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail'));

            
        });

        return $var;

        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
