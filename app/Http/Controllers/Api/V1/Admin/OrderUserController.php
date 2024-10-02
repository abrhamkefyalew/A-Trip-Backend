<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Bid;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\OrderUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\BidResources\BidResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreBidRequest;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrderUserRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrderUserRequest;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserResource;


class OrderUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', OrderUser::class);

        /* $validatedData = */ $request->validate([
            'order_status_search' => [
                'sometimes', 'string', Rule::in([OrderUser::ORDER_STATUS_PENDING, OrderUser::ORDER_STATUS_SET, OrderUser::ORDER_STATUS_START, OrderUser::ORDER_STATUS_COMPLETE]),
            ],
            // Other validation rules if needed
        ]);


        $ordersUsers = OrderUser::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('customer_id_search')) {
            if (isset($request['customer_id_search'])) {
                $customerId = $request['customer_id_search'];

                $ordersUsers = $ordersUsers->where('customer_id', $customerId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('supplier_id_search')) {
            if (isset($request['supplier_id_search'])) {
                $supplierId = $request['supplier_id_search'];

                $ordersUsers = $ordersUsers->where('supplier_id', $supplierId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('driver_id_search')) {
            if (isset($request['driver_id_search'])) {
                $driverId = $request['driver_id_search'];

                $ordersUsers = $ordersUsers->where('driver_id', $driverId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('order_code_search')) {
            if (isset($request['order_code_search'])) {
                $orderCode = $request['order_code_search'];

                $ordersUsers = $ordersUsers->where('order_code', $orderCode);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('is_terminated_search')) {
            if (isset($request['is_terminated_search'])) {
                $isTerminated = $request['is_terminated_search'];

                $ordersUsers = $ordersUsers->where('is_terminated', $isTerminated);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                $ordersUsers = $ordersUsers->where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        if ($request->has('payed_complete_status_search')) {
            if (isset($request['payed_complete_status_search'])) {
                $payedCompleteStatus = $request['payed_complete_status_search'];

                $ordersUsers = $ordersUsers->where('payed_complete_status', $payedCompleteStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }


        $ordersUsersData = $ordersUsers->with('customer', 'vehicleName', 'vehicle', 'supplier', 'driver')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of the organization

        return OrderUserResource::collection($ordersUsersData);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderUserRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            if ($request->has('orders')) {

                $orderIds = [];
                    // since multiple orders can be sent at once 
                        // i will put similar order_code in OrderUserController = for those multiple orders that are sent at once
                        //
                // Generate a random order code
                $uniqueCode = Str::random(20); // Adjust the length as needed

                // Check if the generated code already exists in the database
                while (OrderUser::where('order_code', $uniqueCode)->exists()) {
                    $uniqueCode = Str::random(20); // Regenerate the code if it already exists
                }


                $customer = Customer::find($request['customer_id']);

                if ($customer->is_active !== 1) {
                    return response()->json(['message' => 'Not Active. this customer account is not Active, so you can not make order for this customer'], 401); 
                }

                if ($customer->is_approved !== 1) {
                    return response()->json(['message' => 'Not Approved. this customer account is not Approved, so you can not make order for this customer'], 401); 
                }


                // Now do operations on each of the orders sent
                foreach ($request->safe()->orders as $requestData) {

                    // CHECK REQUEST DATEs (Order dates)

                    // FIRST OF ALL = Check if start_date and end_date are valid dates
                    if (!strtotime($requestData['start_date']) || !strtotime($requestData['end_date'])) {
                        return response()->json(['message' => 'Invalid date format.'], 400);
                    }



                    // order dates // from the request
                    $orderRequestStartDate = Carbon::parse($requestData['start_date'])->toDateString();
                    $orderRequestEndDate = Carbon::parse($requestData['end_date'])->toDateString();


                    // todays date
                    $today = now()->format('Y-m-d');


                    // order start date = must be today or in the days after today , (but start date can not be before today)
                    // Check if start_date is greater than or equal to todays date
                    if ($orderRequestStartDate < $today) {
                        return response()->json(['message' => 'Order Start date must be greater than or equal to today\'s date.'], 400);
                    }
                    // order end date = must be today or in the days after today , (but end date can not be before today)
                    // Check if end_date is greater than or equal to todays date
                    if ($orderRequestEndDate < $today) {
                        return response()->json(['message' => 'Order End date must be greater than or equal to today\'s date.'], 400);
                    }



                    if ($orderRequestStartDate > $orderRequestEndDate) {
                        return response()->json(['message' => 'Order Start Date should not be greater than the Order End Date'], 400);
                    }



                    $orderUser = OrderUser::create([
                        'order_code' => $uniqueCode,

                        'customer_id' => $request['customer_id'],
                        
                        'vehicle_name_id' => $requestData['vehicle_name_id'],

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

                        'status' => OrderUser::ORDER_STATUS_PENDING,    // is PENDING when order is created initially

                        'is_terminated' => 0,    // is 0 (false) when order is created initially
                        'original_end_date' => $requestData['end_date'], // this always holds the end_date of the order as backup, incase the order is terminated.   
                                                                        // if the order is terminated the end_date will be assigned the termination_date.      // So (original_end_date) holds the original order (end_date) as backup 
        
                        'price_total' => null,    // is NULL when the order is created initially
                        'payed_complete_status' => 0,    // is 0 (false) when order is created initially

                        'order_description' => $requestData['order_description'],

                        'with_driver' => (int) (isset($requestData['with_driver']) ? $requestData['with_driver'] : 0),
                        'with_fuel' => (int) (isset($requestData['with_fuel']) ? $requestData['with_fuel'] : 0),
                        'periodic' => (int) (isset($requestData['periodic']) ? $requestData['periodic'] : 0),

                    ]);

                    $orderIds[] = $orderUser->id;



                }

                // WORKS
                $orders = OrderUser::whereIn('id', $orderIds)->with('customer', 'vehicleName', 'vehicle', 'supplier', 'driver', 'bids', 'invoiceUsers')->latest()->get();       // this get the orders created here
                return OrderUserResource::collection($orders);


            }


            
        });

        return $var;

    }

    /**
     * Display the specified resource.
     */
    public function show(OrderUser $orderUser)
    {
        // $this->authorize('view', $orderUser);

        return OrderUserResource::make($orderUser->load('customer', 'vehicleName', 'vehicle', 'supplier', 'driver', 'bids', 'invoiceUsers'));
    }



    /**
     * Store a newly created resource in storage.
     */
    public function storeBid(StoreBidRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            $vehicle = Vehicle::find($request['vehicle_id']);
            $supplier = Supplier::find($vehicle->supplier_id);  // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            $driver = Driver::find($vehicle->driver_id);        // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            $orderUser = OrderUser::find($request['order_id']);

            
            if ($vehicle->vehicle_name_id !== $orderUser->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 401); 
            }

            if (Bid::where('vehicle_id', $vehicle->id)->exists()) {
                return response()->json(['message' => 'you already bid for this order with this vehicle'], 403); 
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


            








            if ($orderUser->status !== OrderUser::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this order is not pending. it is already accepted , started or completed'], 403); 
            }

            if ($orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 403); 
            }

            if ($orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 403); 
            }
            
            if (($orderUser->vehicle_id !== null) || ($orderUser->driver_id !== null) || ($orderUser->supplier_id !== null)) {
                return response()->json(['message' => 'this order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 403); 
            }


            
            

            if ($vehicle->with_driver !== $orderUser->with_driver) {

                if (($vehicle->with_driver === 1) && ($orderUser->with_driver === 0)) {
                    return response()->json(['message' => 'the order does not need a driver'], 403); 
                }
                else if (($vehicle->with_driver === 0) && ($orderUser->with_driver === 1)) {
                    return response()->json(['message' => 'the order needs vehicle with a driver'], 403); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 403); 
                
            }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($vehicle->driver_id === null) && ($orderUser->with_driver === 1)) {
                return response()->json(['message' => 'the vehicle you selected for the order does not have actual driver currently. This Order Needs Vehicle that have Driver'], 403); 
            }
            

            // calculate the initial payment for this bid entry
            $priceTotalFromRequest = (int) $request['price_total'];
            $initialPaymentMultiplierConstant = ((int) Bid::BID_ORDER_INITIAL_PAYMENT)/100;

            $priceInitial = $priceTotalFromRequest * $initialPaymentMultiplierConstant;
            
            
            $bid = Bid::create([
                'order_id' => $request['order_id'],
                'vehicle_id' => $request['vehicle_id'],

                'price_total' => $request['price_total'],
                'price_initial' => $priceInitial,
            ]);
            //
            if (!$bid) {
                return response()->json(['message' => 'Bid Create Failed'], 422);
            }


            $bidValue = Bid::find($bid->id);


            return BidResource::make($bidValue->load('orderUser'));
                 
        });

        return $var;
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderUserRequest $request, OrderUser $orderUser)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderUser $orderUser)
    {
        //
    }
}
