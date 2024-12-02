<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Support\Str;
use App\Models\Organization;
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
        $this->authorize('viewAny', Order::class);

        $orders = Order::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('organization_id_search')) {
            if (isset($request['organization_id_search'])) {
                $organizationId = $request['organization_id_search'];

                $orders = $orders->where('organization_id', $organizationId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('supplier_id_search')) {
            if (isset($request['supplier_id_search'])) {
                $supplierId = $request['supplier_id_search'];

                $orders = $orders->where('supplier_id', $supplierId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('driver_id_search')) {
            if (isset($request['driver_id_search'])) {
                $driverId = $request['driver_id_search'];

                $orders = $orders->where('driver_id', $driverId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_code_search')) {
            if (isset($request['order_code_search'])) {
                $orderCode = $request['order_code_search'];

                $orders = $orders->where('order_code', $orderCode);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('is_terminated_search')) {
            if (isset($request['is_terminated_search'])) {
                $isTerminated = $request['is_terminated_search'];

                $orders = $orders->where('is_terminated', $isTerminated);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                if (!in_array($orderStatus, [Order::ORDER_STATUS_PENDING, Order::ORDER_STATUS_SET, Order::ORDER_STATUS_START, Order::ORDER_STATUS_COMPLETE])) {
                    return response()->json([
                        'message' => 'order_status_search should only be ' . Order::ORDER_STATUS_PENDING . ', ' . Order::ORDER_STATUS_SET . ', ' . Order::ORDER_STATUS_START . ', or ' . Order::ORDER_STATUS_COMPLETE
                    ], 400);
                }

                $orders = $orders->where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }

        }
        if ($request->has('pr_status_search')) {
            if (isset($request['pr_status_search'])) {
                $prStatus = $request['pr_status_search'];

                if (!in_array($prStatus, [null, Order::ORDER_PR_STARTED, Order::ORDER_PR_LAST, Order::ORDER_PR_COMPLETED, Order::ORDER_PR_TERMINATED])) {
                    return response()->json([
                        'message' => 'pr_status_search should only be : null, ' . Order::ORDER_PR_STARTED . ', ' . Order::ORDER_PR_LAST . ', ' . Order::ORDER_PR_COMPLETED . ', or ' . Order::ORDER_PR_TERMINATED
                    ], 400);
                }

                $orders = $orders->where('pr_status', $prStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
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

                // abrham samson check // check abrham samson
                // here check all the sent contract_detail_id s in the request belonged to the same organization_id sent in the request
                
                
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
                    return response()->json(['message' => 'must send organization id.'], 400); 
                }
                if (! isset($request['organization_id'])) { 
                    return response()->json(['message' => 'must set organization id.'], 400); 
                }

                $organization = Organization::find($request['organization_id']);

                if ($organization->is_approved !== 1) {
                    return response()->json(['message' => 'this organization has been Unapproved, please approve the organization first to make an order'], 428); 
                }

                if ($organization->is_active !== 1) {
                    return response()->json(['message' => 'this organization is NOT Active, please activate the organization first to make an order.'], 428); 
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
                        return response()->json(['message' => 'The sent organization_id is NOT equal to the organization_id of the contract in which the selected vehicle_name belongs to. invalid Vehicle Name is selected for the Order. Deceptive request Aborted.'], 422); 
                    }
                    if ($contractDetail->is_available != 1) {
                        // the parent contract of this contract_detail is Terminated
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. The Contract Detail for this Vehicle Name is NOT Available, because the Contract for the requested Vehicle Name is Terminated.'], 410);
                    }
                    if ($contract->terminated_date !== null) {
                        // Contract is terminated
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Vehicle Name is Terminated.'], 410); 
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
                        
                        'vehicle_pr_status' => null,

                        'order_description' => $requestData['order_description'],                                                                                   
                    ]);

                    $orderIds[] = $order->id;
                    

                    
                }

                // WORKS
                $orders = Order::whereIn('id', $orderIds)->with('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail', 'invoices', 'trips')->latest()->get();       // this get the orders created here
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
        $this->authorize('view', $order);
        
        return OrderResource::make($order->load('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail', 'invoices', 'trips'));
    }



    /**
     * Update the specified resource in storage.
     * 
     * to Accept Order = ORDER_STATUS_SET
     */
    public function acceptOrder(AcceptOrderRequest $request, Order $order)
    {
        // do AUTH here or in the controller

        $var = DB::transaction(function () use ($request, $order) {

            $vehicle = Vehicle::find($request['vehicle_id']);
            $supplier = Supplier::find($vehicle->supplier_id);  // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            $driver = Driver::find($vehicle->driver_id);        // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            
            if ($vehicle->vehicle_name_id !== $order->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 422); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 409); 
            }


            // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            if ($driver) {
                if ($driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 428); 
                }
                if ($driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 428); 
                }
            }
            // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            if ($supplier) {
                if ($supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 428); 
                }
                if ($supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 428); 
                }
            }


            








            if ($order->status !== Order::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this order is not pending. it is already accepted , started or completed'], 409); 
            }

            if ($order->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 410); 
            }

            if ($order->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 410); 
            }
            
            if (($order->vehicle_id !== null) || ($order->driver_id !== null) || ($order->supplier_id !== null)) {
                return response()->json(['message' => 'this order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 409); 
            }


            // TODO
            // dd("order->contractDetail->with_driver" . $order->contractDetail->with_driver);

            if ($vehicle->with_driver !== $order->contractDetail->with_driver) {        // TEST IF THIS DOES WORK = $order->contractDetail->with_driver         // check abrham samson

                if (($vehicle->with_driver === 1) && ($order->contractDetail->with_driver === 0)) {
                    return response()->json(['message' => 'the order does not need a driver'], 422); 
                }
                else if (($vehicle->with_driver === 0) && ($order->contractDetail->with_driver === 1)) {
                    return response()->json(['message' => 'the order needs vehicle with a driver'], 422); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 422); 
                
            }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($vehicle->driver_id === null) && ($order->contractDetail->with_driver === 1)) {
                return response()->json(['message' => 'the vehicle you selected for the order does not have actual driver currently. This Order Needs Vehicle that have Driver'], 422); 
            }
            

            // CHECK IF THE CONTRACT DETAIL IS NOT AVAILABLE
            if ($order->contractDetail->is_available !== 1) { // TEST IF THIS DOES WORK = $order->contractDetail->with_driver       // also test if this condition is needed   // check abrham samson
                return response()->json(['message' => 'this order contract_detail have is_available 0 currently for some reason, the contract_detail of this order should have is_available 1'], 410); 
            }

            // TODO check if the contract itself is expired or Terminated


            $withDriver = $order->contractDetail->with_driver;
            //
            $driverId = $withDriver === 1 ? $vehicle->driver_id : null;
            //
            //
            $success = $order->update([
                'vehicle_id' => $request['vehicle_id'],
                'driver_id' => $driverId,
                'supplier_id' => $vehicle->supplier_id,
                'status' => Order::ORDER_STATUS_SET,
            ]);
            //            
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail', 'organization', 'invoices', 'trips'));
                 
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
        // do AUTH here or in the controller

        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_ON_TRIP       - ALSO order begin_date will be set to today()      - ALSO order status will be ORDER_STATUS_START

        $var = DB::transaction(function () use ($request, $order) {


            // if ADIAMT wants to rent their own vehicles, They Can Register as SUPPLIERs Themselves
            // if (!$order->driver && !$order->supplier) { 
            //     return response()->json(['message' => 'the order at least should be accepted by either a driver or supplier'], 428); 
            // }

            if ($order->driver) {
                if ($order->driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 428); 
                }
                if ($order->driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 428); 
                }
            }
            if ($order->supplier) {
                if ($order->supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 428); 
                }
                if ($order->supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 428); 
                }
            }


            if ($order->status !== Order::ORDER_STATUS_SET) {
                return response()->json(['message' => 'this order is not SET (ACCEPTED). order should be SET (ACCEPTED) before it can be STARTED.'], 428); 
            }

            if ($order->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 410); 
            }

            if ($order->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 410); 
            }


            // CHECK IF THE CONTRACT DETAIL IS NOT AVAILABLE
            if ($order->contractDetail->is_available !== 1) { // TEST IF THIS DOES WORK  // check abrham samson
                return response()->json(['message' => 'this order contract_detail have is_available 0 currently for some reason, the contract_detail of this order should have is_available 1'], 410); 
            }

            if ($order->contractDetail->with_fuel === 1) { 
                return response()->json(['message' => 'this order requires fuel to be filled by adiamat. so it needs a driver to fill log-sheet for every trip. therefore the order needs a driver account be started, so a driver can only start this order'], 422); 
            }

            // check abrham samson
            // is the following condition required 
            // if ($order->contractDetail->periodic === 1) { 
            //     return response()->json(['message' => 'this order is periodic. so the order needs a driver to be started'], 422); 
            // }



            // TODO check if the contract itself is expired or Terminated

            
            // todays date
            $today = now()->format('Y-m-d');

            $orderStartDate = Carbon::parse($order->start_date)->toDateString();

            if ($orderStartDate > $today) {
                return response()->json(['message' => 'this order can not be made to begin now yet. the start date of the order is still in the future. you must wait another days and reach the start date of the order to start it.'], 400);
            }            



            $success = $order->update([
                'status' => Order::ORDER_STATUS_START,
                'begin_date' => today()->toDateString(),
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 500);
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
                return response()->json(['message' => 'Vehicle Update Failed'], 500);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail', 'organization', 'invoices', 'trips'));

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
        // do AUTH here or in the controller
        
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_AVAILABLE // order status = ORDER_STATUS_COMPLETE // and order end_date = today()

        $var = DB::transaction(function () use ($request, $order) {

            if ($order->status !== Order::ORDER_STATUS_START) {
                return response()->json(['message' => 'this order is not STARTED. order should be STARTED before it can be COMPLETED.'], 428); 
            }



            /*
                The reason i did the following TWO conditions is because of PR asking. and for the following TWO scenarios
                        //
                        NOW
                    1. if a we COMPLETE an order before it's intended end_date, 
                                    - then NO Problem, - there sill NOT be any problems caused by this scenario
                             => SO the end_date of the order will be the date the order is completed.    i.e. in other words the order end_date will be today() [today being the date the order is being completed].
                        //
                        //
                        BUT
                    2. if a we COMPLETE an order way after it's intended end_date (or Complete the order on a date that is EQUAL to its intended end_date), 
                                    - that will create extra days that the organization is asked PR by Adiamat
                                    - that will create extra days that the vehicle (supplier) itself is payed by Adiamat
                             => SO the end_date of the order will be the already existing end_date of the order.    i.e. in other words the order end_date will NOT be changed.
            */
            //
            //
            // todays date
            $today = now()->format('Y-m-d');
            //
            // 
            // we do this Because if the order end date is in the future still and we sent ORDER_STATUS_COMPLETE, the project still charges the the customer for the remaining days until the project end_date is reached, even if the "order status is set to COMPLETE"
            // solution is the above, if we make the order end date = today(), when order is set to Complete , then the order end_date will match the order Complete status,  and there will not be any left over dates we ask payment to after the order is complete
            //
            //
            //
            $orderEndDate = Carbon::parse($order->end_date)->toDateString();
            //
            //
            //
            // if a we COMPLETE an order before it's intended end_date,   - or -   if the order that is about to be completed does NOT reach its end_date,
            // if today's date does is less than the order's end_date
            //
            // => SO the end_date of the order will be the date the order is completed.    i.e. in other words the order end_date will be today() [today being the date the order is being completed].
            if ($today < $orderEndDate) {

                $success = $order->update([
                    'status' => Order::ORDER_STATUS_COMPLETE,
                    'end_date' => $today,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'Order Update Failed'], 500);
                }

            }
            //
            // if a we COMPLETE an order way after it's intended end_date (or Complete the order on a date that is EQUAL to its intended end_date),   - or -   if the order that is about to be completed has already past its end_date (or at least its on its end_date (EQUALs to its end_date) )
            // if today's date is greater than (or equals to end date) the order's end_date
            //
            //  => SO the end_date of the order will be the already existing end_date of the order.    i.e. in other words the order end_date will NOT be changed.
            if ($today >= $orderEndDate) {

                $success = $order->update([
                    'status' => Order::ORDER_STATUS_COMPLETE,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'Order Update Failed'], 500);
                }

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
                return response()->json(['message' => 'Vehicle Update Failed'], 500);
            }

            $updatedOrder = Order::find($order->id);
                
            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail', 'organization', 'invoices', 'trips'));

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
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 422); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 409); 
            }


            // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            if ($driver) {
                if ($driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 428); 
                }
                if ($driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 428); 
                }
            }
            // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            if ($supplier) {
                if ($supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 428); 
                }
                if ($supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 428); 
                }
            }


            if ($order->status !== Order::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'an order must be pending to be updated'], 422); 
            }



            $contractDetail = null;
            // this contract_detail_id should be owned by the organization that the super_admin is making the order to
            if ($request->has('contract_detail_id') && isset($request['contract_detail_id'])) {
                $contractDetail = ContractDetail::where('id', $request['contract_detail_id'])->first();

                if ($contractDetail) {
                    if ($contractDetail->is_available != 1) {
                        // the parent contract of this contract_detail is Terminated
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. The Contract Detail for this Vehicle Name is NOT Available, because the Contract for the requested Vehicle Name is Terminated.'], 410);
                    }

                    if ($vehicle->vehicle_name_id !== $contractDetail->vehicle_name_id) {
                        return response()->json(['message' => 'the vehicle\'s vehicle_name_id is NOT equal to contractDetail\'s vehicle_name_id. Deceptive request Aborted.'], 422); 
                    }

                    $contract = Contract::where('id', $contractDetail->contract_id)->first();

                    if ($contract) {
                        if ($contract->terminated_date !== null) {
                            // Contract is terminated
                            return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Vehicle Name is Terminated.'], 410); 
                        }

                        if ($request->has('organization_id') && isset($request['organization_id'])) {
                            if ($request['organization_id'] != $contract->organization_id) {
                                return response()->json(['message' => 'The sent organization_id is NOT equal to the contract\'s organization_id for the selected vehicle_name. invalid Vehicle Name (Contract Detail) is selected for the Order. Deceptive request Aborted.'], 422); 
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


            if ($request->has('vehicle_id') && isset($request['vehicle_id'])) {
                $order->vehicle_id = $request['vehicle_id']; // this is duplicate , since the $success = $order->update($request->validated()); will have updated it , since it will find vehicle_id in $request->validated()
                $order->driver_id = $vehicle->driver_id;
                $order->supplier_id = $vehicle->supplier_id;
            }
            if ($request->has('is_terminated') && isset($request['is_terminated'])) {
                if ($request['is_terminated'] == 1) {
                    if ($order->status === Order::ORDER_STATUS_START) {
                        return response()->json(['message' => 'this order can not be terminated. because the order is already started'], 422); 
                    }
                    if ($order->status === Order::ORDER_STATUS_COMPLETE) {
                        return response()->json(['message' => 'this order can not be terminated. because the order is already completed'], 422); 
                    }
    
                    $order->end_date = today()->toDateString();
                }
            }
            if ($request->has('contract_detail_id') && isset($request['contract_detail_id'])) {
                // should the contract of the contract_detail be checked also // check abrham samson
                $order->vehicle_name_id = $contractDetail->vehicle_name_id;
                
            }

            $order->save();




            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 500);
            }

            $updatedOrder = Order::find($order->id);


            return OrderResource::make($updatedOrder->load('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail', 'organization', 'invoices', 'trips'));

            
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
