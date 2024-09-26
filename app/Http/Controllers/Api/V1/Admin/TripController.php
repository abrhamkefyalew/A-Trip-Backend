<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\TripResources\TripResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreTripRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateTripRequest;


class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Trip::class);

        /* $validatedData = */ $request->validate([
            'trip_status_search' => [
                'sometimes', 'string', Rule::in([Trip::TRIP_STATUS_APPROVED, Trip::TRIP_STATUS_NOT_APPROVED]),
            ],
            'trip_status_payment_search' => [
                'sometimes', 'string', Rule::in([Trip::TRIP_PAID, Trip::TRIP_NOT_PAID]),
            ],

            // Other validation rules if needed
        ]);


        $trips = Trip::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('driver_id_search')) {
            if (isset($request['driver_id_search'])) {
                $driverId = $request['driver_id_search'];

                $trips = $trips->where('driver_id', $driverId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('order_id_search')) {
            if (isset($request['order_id_search'])) {
                $orderId = $request['order_id_search'];

                $trips = $trips->where('order_id', $orderId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('organization_user_id_search')) {
            if (isset($request['organization_user_id_search'])) {
                $organizationUserId = $request['organization_user_id_search'];

                $trips = $trips->where('organization_user_id', $organizationUserId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('trip_status_search')) {
            if (isset($request['trip_status_search'])) {
                $tripStatus = $request['trip_status_search'];

                $trips = $trips->where('status', $tripStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('trip_status_payment_search')) {
            if (isset($request['trip_status_payment_search'])) {
                $tripStatusPayment = $request['trip_status_payment_search'];

                $trips = $trips->where('status_payment', $tripStatusPayment);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }


        $tripsData = $trips->with('order', 'organizationUser')->latest()->paginate(FilteringService::getPaginate($request));

        return TripResource::collection($tripsData);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        // $this->authorize('view', $trip);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        //
    }
}
