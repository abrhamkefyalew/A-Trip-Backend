<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Contract;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ContractDetail;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\OrganizationUserRequests\StoreOrderRequest;
use App\Http\Requests\Api\V1\OrganizationUserRequests\UpdateOrderRequest;
use App\Http\Resources\Api\V1\OrderResources\OrderForOrganizationResource;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);
        
        $orders = Order::where('organization_id', $organizationUser->organization_id)->with('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of the organization

        return OrderForOrganizationResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        //
        // dd($request);
        $var = DB::transaction(function () use ($request) {

            if ($request->has('orders')) {
                
                $orderIds = [];
                    // since multiple orders can be sent at once 
                        // i will put similar order_code in OrderController = for those multiple orders that are sent at once
                        //
                // Generate a unique random order code
                $uniqueCode = Str::random(20); // Adjust the length as needed

                // Check if the generated code already exists in the database
                while (Order::where('order_code', $uniqueCode)->exists()) {
                    $uniqueCode = Str::random(20); // Regenerate the code if it already exists
                }

                // get the logged in organization User
                $user = auth()->user();
                $organizationUser = OrganizationUser::find($user->id);

                // check if the organizationUser is organization admin
                if ($organizationUser->is_admin !== 1) {
                    return response()->json(['message' => 'UnAuthorized. you are not organization Admin'], 401); 
                }


                // Now do operations on each of the orders sent
                foreach ($request->safe()->orders as $requestData) {

                    // this contract_detail_id should be owned by the organization that the order requester belongs in
                    $contractDetail = ContractDetail::where('id', $requestData['contract_detail_id'])->first();
                    $contract = Contract::where('id', $contractDetail->contract_id)->first();

                    if ($organizationUser->organization_id != $contract->organization_id) {
                        return response()->json(['message' => 'invalid Vehicle Name is selected for the Order. or invalid Contract-Contact_Detail Selected. Deceptive request Aborted.'], 401); 
                    }
                    if ($contract->is_active != 1) {
                        // contract not active
                        return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Vehicle Name is Deactivated or Not Active.'], 404); 
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

                    // todays date
                    $today = now()->format('Y-m-d');

                    /* 
                        // LOG  -  TEST - - - Remove this
                            // used to check that = order start_date can not be before the contract creation date ,     but order start_data can be on the day of contract creation date and after
                                $aa = $orderRequestStartDate < $contractStartDate;
                                dd($orderRequestStartDate . " < " . $contractStartDate . " = " . ($aa ? 'true' : 'false'));

                        // OUTPUT should be   -   -   -   -   - // it should output the following
                                // "2024-12-27 < 2024-12-27 = false"
                    */

                    
                    // order start date = must be today or after today , (but start date can not be before today)
                    // Check if start_date is greater than or equal to today's date
                    if ($orderRequestStartDate < $today) {
                        return response()->json(['message' => 'Order Start date must be greater than or equal to today\'s date.'], 400);
                    }
                    // order end date = must be today or after today , (but end date can not be before today)
                    // Check if end_date is greater than or equal to today's date
                    if ($orderRequestEndDate < $today) {
                        return response()->json(['message' => 'Order End date must be greater than or equal to today\'s date.'], 400);
                    }

                
                    if ($orderRequestStartDate < $contractStartDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order start_date can not be before the contract creation date'], 400);
                    }
                    if ($orderRequestStartDate > $contractEndDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order start_date can not be after the contract expiration date'], 400);
                    }
                    if ($orderRequestEndDate < $contractStartDate) {
                        return response()->json(['message' => 'Order Start date and end date must fall within the contract period.    order end_date can not be before the contract creation date'], 400);
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

                        'organization_id' => $organizationUser->organization_id,
                        'contract_detail_id' => $requestData['contract_detail_id'],
                        
                        'vehicle_name_id' => $contractDetail->vehicle_name_id,

                        'vehicle_id' => null,   // is NULL when the order is created initially
                        'driver_id' => null,    // is NULL when the order is created initially
                        'supplier_id' => null,    // is NULL when the order is created initially

                        'start_date' => $requestData['start_date'],
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
                $orders = Order::whereIn('id', $orderIds)->with('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail')->latest()->paginate(FilteringService::getPaginate($request));       // this get the orders created here
                return OrderForOrganizationResource::collection($orders);
            
            }


            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        // check if the requested order belongs to the organization of the requester organizationUser
        // this order should be owned by the organization that the order requester belongs in

        // get the logged in organization User
        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);

        
        if ($organizationUser->organization_id != $order->organization_id) {
            // this order is NOT be owned by the organization that the order requester belongs in // so i return error and abort
            return response()->json(['message' => 'invalid Order is selected or Requested. or the requested Order is not found. Deceptive request Aborted.'], 401);
        }


        return OrderForOrganizationResource::make($order->load('vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, string $id)
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
