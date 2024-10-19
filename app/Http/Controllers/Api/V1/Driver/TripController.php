<?php

namespace App\Http\Controllers\Api\V1\Driver;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\DriverRequests\StoreTripRequest;
use App\Http\Requests\Api\V1\DriverRequests\UpdateTripRequest;
use App\Http\Resources\Api\V1\TripResources\TripForDriverResource;
use App\Models\OrganizationUser;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /* $validatedData = */ $request->validate([
            'trip_status_search' => [
                'sometimes', 'string', Rule::in([Trip::TRIP_STATUS_APPROVED, Trip::TRIP_STATUS_NOT_APPROVED]),
            ],
            'trip_status_payment_search' => [
                'sometimes', 'string', Rule::in([Trip::TRIP_PAID, Trip::TRIP_NOT_PAID]),
            ],

            // Other validation rules if needed
        ]);

        $user = auth()->user();
        $driver = Driver::find($user->id);

        $trips = Trip::where('driver_id', $driver->id);

        // use Filtering service OR Scope to do this
        if ($request->has('order_id_search')) {
            if (isset($request['order_id_search'])) {
                $orderId = $request['order_id_search'];

                $trips = $trips->where('order_id', $orderId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('organization_user_id_search')) {
            if (isset($request['organization_user_id_search'])) {
                $organizationUserId = $request['organization_user_id_search'];

                $trips = $trips->where('organization_user_id', $organizationUserId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('trip_status_search')) {
            if (isset($request['trip_status_search'])) {
                $tripStatus = $request['trip_status_search'];

                $trips = $trips->where('status', $tripStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('trip_status_payment_search')) {
            if (isset($request['trip_status_payment_search'])) {
                $tripStatusPayment = $request['trip_status_payment_search'];

                $trips = $trips->where('status_payment', $tripStatusPayment);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }


        $tripsData = $trips->with('order', 'organizationUser')->latest()->paginate(FilteringService::getPaginate($request));

        return TripForDriverResource::collection($tripsData);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            // get the driver identity
            $user = auth()->user();
            $driver = Driver::find($user->id);

            // check order
            $order = Order::find($request['order_id']);

            if ($order->driver_id !== $driver->id) {
                return response()->json(['message' => 'invalid Order is selected. Deceptive request Aborted.'], 403); 
            }
            

            if ($order->status !== Order::ORDER_STATUS_START) {
                return response()->json(['message' => 'this order is not STARTED. the order should have status: START before trip creation. order should be STARTED before a Trip can be created under it.'], 428); 
            }


            if ($order->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 410); 
            }

            if ($order->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 410); 
            }


            if ($order->contractDetail->with_fuel !== 1) {
                return response()->json(['message' => 'Trip can only be created for orders that require fuel. this Trip can Not be created, because its parent order does not require fuel'], 422);
            }

            // CHECK IF THE CONTRACT DETAIL IS NOT AVAILABLE
            if ($order->contractDetail->is_available !== 1) {
                return response()->json(['message' => 'this order contract_detail have is_available 0 currently for some reason, the contract_detail of this order should have is_available 1'], 422); 
            }

            if ($order->contractDetail->with_driver !== 1) {
                return response()->json(['message' => 'Trip can not be created for this order, since the order does not need driver. only orders that require a driver are allowed to have Trip'], 422);
            }

            if ($order->contractDetail->periodic === 1) {
                return response()->json(['message' => 'Trip can not be created for this order, because this order is periodic'], 422);
            }


            // check organization
            if ($order->organization->is_active !== 1) {
                return response()->json(['message' => 'Trip can not be created for this order, because this organization that owns the order is NOT Active. organization should be Activated first to create trip'], 403);
            }

            if ($order->organization->is_approved !== 1) {
                return response()->json(['message' => 'Trip can not be created for this order, because this organization that owns the order is Unapproved. organization should be Approved first to create trip'], 403);
            }

            // check organization User
            $organizationUser = OrganizationUser::find($request['organization_user_id']);
            
            if ($organizationUser->organization_id != $order->organization_id) {
                return response()->json(['message' => 'invalid Organization User is selected or Requested. or the Organization User provided is not found. Deceptive request Aborted.'], 403);
            }

            if ($organizationUser->is_active != 1) {
                return response()->json(['message' => 'this organization User has been is De-Activated, please activate the organization user first to make a trip this user.'], 422); 
            }


            // check contract
            $contract = Contract::find($order->contractDetail->contract_id);

            if (!$contract) {
                // contract not found
                return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Order does NOT exist.'], 404); 
            }

            if ($contract->terminated_date !== null) {
                // Contract is terminated
                return response()->json(['message' => 'Not Found - the server cannot find the requested resource. the Contract for the requested Order is Terminated.'], 410); 
            }



            // todays date
            $today = now()->format('Y-m-d');

            $orderStartDate = Carbon::parse($order->start_date)->toDateString();

            // a less likely situation or condition
            if ($orderStartDate > $today) {
                return response()->json(['message' => 'Trip can not be created for this order yet. the start date of the order is still in the future. you must wait another days and reach the start date of the order to create trip for it it.'], 400);
            }


            $trip = Trip::create([
                'order_id' => $request['order_id'],
                'driver_id' => $driver->id,
                'organization_user_id' => $request['organization_user_id'],

                'start_dashboard' => $request['start_dashboard'],

                'source' => $request['source'],
                'destination' => $request['destination'],

                'trip_date' => $request['trip_date'],

                'trip_description' => $request['trip_description'],

                'status' => Trip::TRIP_STATUS_NOT_APPROVED,
                'status_payment' => Trip::TRIP_NOT_PAID,
                                                                                                  
            ]);
            //
            if (!$trip) {
                return response()->json(['message' => 'Trip Create Failed'], 500);
            }


            $tripValue = Trip::find($trip->id);


            return TripForDriverResource::make($tripValue->load('order', 'organizationUser'));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {

            // get the driver identity
            $user = auth()->user();
            $driver = Driver::find($user->id);

            if ($trip->driver_id !== $driver->id) {
                return response()->json(['message' => 'invalid Trip. Deceptive request Aborted.'], 403); 
            }


            if ($trip->status === Trip::TRIP_STATUS_APPROVED) {
                return response()->json(['message' => 'this Trip is already APPROVED , so no further updates on this trip is not allowed.'], 409); 
            }


            $startDashboard = (int) $request['start_dashboard'];
            $endDashboard = (int) $request['end_dashboard'];


            if ($endDashboard < $startDashboard) {
                return response()->json(['message' => 'the end dashboard reading should not be less than the start dashboard reading.'], 403); 
            }
            


            $success = $trip->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Trip Update Failed'], 500);
            }

            $updatedTrip = Trip::find($trip->id);

            return TripForDriverResource::make($updatedTrip->load('order', 'organizationUser'));
            
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        //
    }
}
