<?php

namespace App\Http\Controllers\Api\V1\Customer;

use Carbon\Carbon;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Jobs\SendSmsJob;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\OrderUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\CustomerRequests\StoreOrderUserRequest;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserForCustomerResource;

class OrderUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = auth()->user();
        $customer = Customer::find($user->id);
        
        $ordersUsers = OrderUser::where('customer_id', $customer->id)->with('vehicleName', 'vehicle', 'driver')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of the organization

        return OrderUserForCustomerResource::collection($ordersUsers);
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


                $user = auth()->user();
                $customer = Customer::find($user->id);

                if ($customer->is_active !== 1) {
                    return response()->json(['message' => 'Not Active. your account is not Active, so you can not make order. please activate your account'], 428); 
                }

                if ($customer->is_approved !== 1) {
                    return response()->json(['message' => 'Not Approved. your account is not Approved, so you can not make order'], 401); 
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

                        'customer_id' => $customer->id,
                        
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
                        'paid_complete_status' => 0,    // is 0 (false) when order is created initially

                        'vehicle_pr_status' => null,

                        'order_description' => $requestData['order_description'],

                        'with_driver' => (int) (isset($requestData['with_driver']) ? $requestData['with_driver'] : 0),
                        'with_fuel' => 0,
                        'periodic' => 0,

                    ]);

                    $orderIds[] = $orderUser->id;


                    // send SMS to the vehicle owners that holds the specific_vehicle_name_id this order requires
                    if ($requestData['with_driver'] == 0) {

                        $supplierIds = Vehicle::where('vehicle_name_id', $requestData['vehicle_name_id'])
                            ->whereNotNull('supplier_id') // Ensures NULLs are not included
                            ->pluck('supplier_id')
                            ->unique();

                        $phoneNumbersOfSuppliers = Supplier::whereIn('id', $supplierIds)->pluck('phone_number');


                        // the below code is the optimized version of the above
                        // 
                        // $phoneNumbersOfSuppliers = Supplier::whereHas('vehicles', function ($query) use ($requestData['vehicle_name_id']) {
                        //     $query->where('vehicle_name_id', $requestData['vehicle_name_id']);
                        // })->pluck('phone_number')->unique();
                        

                        try {
                            foreach ($phoneNumbersOfSuppliers as $phoneNumber) {
                                // Dispatch SMS job for each phone number
                                SendSmsJob::dispatch($phoneNumber, 'Adiamat Vehicle Rental: there is an order with your vehicle. Needed Vehicle: ' . $requestData['vehicle_name_id'])
                                    ->onQueue('sms');
                            }
                        } catch (\Throwable $e) {
                            // Log the exception or handle it as needed
                            Log::error("Failed to dispatch SMS job: " . $e->getMessage());
                        }

                        Log::info("SMS job dispatched successfully to: $phoneNumber for Vehicle: " . $requestData['vehicle_name_id']);
                    }
                    else if ($requestData['with_driver'] == 1) {

                        $driverIds = Vehicle::where('vehicle_name_id', $requestData['vehicle_name_id'])
                            ->whereNotNull('driver_id') // Ensures NULLs are not included
                            ->pluck('driver_id');

                        $phoneNumbersOfDrivers = Driver::whereIn('id', $driverIds)->pluck('phone_number');


                        // the below code is the optimized version of the above
                        // 
                        // $phoneNumbersOfDrivers = Driver::whereHas('vehicles', function ($query) use ($requestData['vehicle_name_id']) {
                        //     $query->where('vehicle_name_id', $requestData['vehicle_name_id']);
                        // })->pluck('phone_number');
                        

                        try {
                            foreach ($phoneNumbersOfDrivers as $phoneNumber) {
                                // Dispatch SMS job for each phone number
                                SendSmsJob::dispatch($phoneNumber, 'Adiamat Vehicle Rental: there is an order with your vehicle. Needed Vehicle: ' . $requestData['vehicle_name_id'])
                                    ->onQueue('sms');
                            }
                        } catch (\Throwable $e) {
                            // Log the exception or handle it as needed
                            Log::error("Failed to dispatch SMS job: " . $e->getMessage());
                        }

                        Log::info("SMS job dispatched successfully to: $phoneNumber for Vehicle: " . $requestData['vehicle_name_id']);
                    }


                }

                // WORKS
                $orders = OrderUser::whereIn('id', $orderIds)->with('vehicleName', 'vehicle', 'driver', 'bids', 'invoiceUsers')->latest()->get();       // this get the orders created here
                return OrderUserForCustomerResource::collection($orders);


            }


            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderUser $orderUser)
    {
        //
        $user = auth()->user();
        $customer = Customer::find($user->id);

        
        if ($customer->id != $orderUser->customer_id) {
            return response()->json(['message' => 'invalid Order is selected or Requested. or the requested Order is not found. Deceptive request Aborted.'], 403);
        }


        return OrderUserForCustomerResource::make($orderUser->load('vehicleName', 'vehicle', 'driver', 'bids', 'invoiceUsers'));
    }



    
    



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderUser $orderUser)
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
