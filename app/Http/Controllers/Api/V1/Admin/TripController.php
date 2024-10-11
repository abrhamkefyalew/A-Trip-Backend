<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
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
    public function approveTrip(Request $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {
            
            if ($trip->status === Trip::TRIP_STATUS_APPROVED) {
                return response()->json(['message' => 'this Trip is already APPROVED.'], 403); 
            }

            if ($trip->order_id === null ||
                $trip->driver_id === null ||
                $trip->organization_user_id === null ||
                $trip->start_dashboard === null ||
                $trip->end_dashboard === null ||
                $trip->source === null ||
                $trip->destination === null ||
                $trip->trip_date === null ||
                $trip->status === null ||
                $trip->status_payment === null) {
                
                return response()->json(['error' => 'Trip Can Not be Approved, Because some important values of the Trip are Not filled yet. Thr Driver should complete filling all the required Trip Values Before it can be approved.'], 400);
            }
            

            $success = $trip->update([
                'status' => Trip::TRIP_STATUS_APPROVED,
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Trip Update Failed'], 422);
            }

            
            $updatedTrip = Trip::find($trip->id);

            // since this condition is for the organization admin we return him the organizationUser relation
            return TripResource::make($updatedTrip->load('order', 'driver', 'organizationUser'));
            
        });

        return $var;
    }


    /**
     * Update the specified resource in storage.
     */
    public function payTrip(Request $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {
            
            if ($trip->status !== Trip::TRIP_STATUS_APPROVED) {
                return response()->json(['message' => 'this Trip is should be APPROVED first.'], 403); 
            }

            if ($trip->status_payment === Trip::TRIP_PAID) {
                return response()->json(['message' => 'this Trip is already PAID.'], 403); 
            }

            if ($trip->order_id === null ||
                $trip->driver_id === null ||
                $trip->organization_user_id === null ||
                $trip->start_dashboard === null ||
                $trip->end_dashboard === null ||
                $trip->source === null ||
                $trip->destination === null ||
                $trip->trip_date === null ||
                $trip->status === null ||
                $trip->status_payment === null) {
                
                return response()->json(['error' => 'Trip Can Not be Approved, Because some important values of the Trip are Not filled yet. Thr Driver should complete filling all the required Trip Values Before it can be approved.'], 400);
            }
            
            

            $success = $trip->update([
                'status_payment' => Trip::TRIP_PAID,
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Trip Update Failed'], 422);
            }

            
            $updatedTrip = Trip::find($trip->id);

            // since this condition is for the organization admin we return him the organizationUser relation
            return TripResource::make($updatedTrip->load('order', 'driver', 'organizationUser'));
            
        });

        return $var;
    }




    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {

            if ($request->has('organization_user_id') && isset($request['organization_user_id'])) {
                // check organization User
                $organizationUser = OrganizationUser::find($request['organization_user_id']);
                
                if ($organizationUser->organization_id != $trip->order->organization_id) {
                    return response()->json(['message' => 'invalid Organization User is selected or Requested. or the Organization User provided is not found. Deceptive request Aborted.'], 401);
                }
            }

            $startDashboard = (int) $request['start_dashboard'];
            $endDashboard = (int) $request['end_dashboard'];


            if ($endDashboard < $startDashboard) {
                return response()->json(['message' => 'the end dashboard reading should not be less than the start dashboard reading.'], 403); 
            }
            


            $success = $trip->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Trip Update Failed'], 422);
            }

            $updatedTrip = Trip::find($trip->id);

            return TripResource::make($updatedTrip->load('order', 'organizationUser'));


            
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        // $this->authorize('delete', $trip);

        $var = DB::transaction(function () use ($trip) {

            $trip->delete();

            return response()->json(true, 200);

        });

        return $var;
    }


    public function restore(string $id)
    {
        $trip = Trip::withTrashed()->find($id);

        // $this->authorize('restore', $trip);

        $var = DB::transaction(function () use ($trip) {
            
            if (!$trip) {
                abort(404);    
            }
    
            $trip->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }


}
