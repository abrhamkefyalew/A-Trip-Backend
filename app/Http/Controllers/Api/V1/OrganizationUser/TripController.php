<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use App\Models\Trip;
use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\Api\V1\TripResources\TripForOrganizationResource;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);


        if ($organizationUser->is_admin === 1) {

            $organizationId = $organizationUser->organization_id;

            $trips = Trip::whereHas('order', function (Builder $builder) use ($organizationId) {
                $builder->where('orders.organization_id', $organizationId);
            });


             // since this condition is for the organization admin we return him the organizationUser relation
            $tripsData = $trips->with('order', 'driver', 'organizationUser')->latest()->paginate(FilteringService::getPaginate($request));

            return TripForOrganizationResource::collection($tripsData);

        }
        else {
            $tripsValue = Trip::where('organization_user_id', $organizationUser->id)
                ->with('order', 'driver')   // since this condition is for the organization user we do not return him the organizationUser relation
                ->latest()
                ->paginate(FilteringService::getPaginate($request));

            return TripForOrganizationResource::collection($tripsValue);
        }

        
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
    public function show(Trip $trip)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function approveTrip(Request $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {
            
            if ($trip->status === Trip::TRIP_STATUS_APPROVED) {
                return response()->json(['message' => 'this Trip is already APPROVED.'], 409); 
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
                
                return response()->json(['error' => 'Trip Can Not be Approved, Because some important values of the Trip are Not filled yet. Thr Driver should complete filling all the required Trip Values Before it can be approved.'], 428);
            }
            
            
            $user = auth()->user();
            $organizationUser = OrganizationUser::find($user->id);


            if ($organizationUser->is_admin === 1) {

                if ($organizationUser->organization_id !== $trip->order->organization_id) {
                    return response()->json(['message' => 'invalid Trip. Deceptive request Aborted.'], 403);
                }

                $success = $trip->update([
                    'status' => Trip::TRIP_STATUS_APPROVED,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'Trip Update Failed'], 500);
                }

                
                $updatedTrip = Trip::find($trip->id);
    
                // since this condition is for the organization admin we return him the organizationUser relation
                return TripForOrganizationResource::make($updatedTrip->load('order', 'driver', 'organizationUser'));

            }
            else {
                
                if ($organizationUser->id !== $trip->organization_user_id) {
                    return response()->json(['message' => 'invalid Trip. Deceptive request Aborted.'], 403);
                }

                $success = $trip->update([
                    'status' => Trip::TRIP_STATUS_APPROVED,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'Trip Update Failed'], 500);
                }

                
                $updatedTrip = Trip::find($trip->id);
    
                // since this condition is for the organization user we do not return him the organizationUser relation
                return TripForOrganizationResource::make($updatedTrip->load('order', 'driver'));

            }
            
        });

        return $var;
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
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
