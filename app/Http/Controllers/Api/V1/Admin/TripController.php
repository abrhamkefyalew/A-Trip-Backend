<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Trip;
use App\Models\InvoiceTrip;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ContractDetail;
use Illuminate\Validation\Rule;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\TripResources\TripResource;
use App\Http\Requests\Api\V1\AdminRequests\PayTripRequest;
use App\Http\Requests\Api\V1\AdminRequests\StoreTripRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateTripRequest;
use App\Services\Api\V1\Admin\Payment\TeleBirr\TeleBirrTripPaymentService;


class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Trip::class);

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
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
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


        $tripsData = $trips->with('order', 'driver', 'organizationUser' /*, 'invoiceTrips' */ )->latest()->paginate(FilteringService::getPaginate($request));

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
        $this->authorize('view', $trip);

        return TripResource::make($trip->load('order', 'driver', 'organizationUser' /*, 'invoiceTrips' */));
    }


    
    /**
     * Update the specified resource in storage.
     */
    public function approveTrip(Request $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {
            
            if ($trip->status == Trip::TRIP_STATUS_APPROVED) {
                return response()->json(['message' => 'this Trip is already APPROVED.'], 409); 
            }

            if ($trip->order_id === null ||
                $trip->driver_id === null ||
                $trip->organization_user_id === null ||
                $trip->start_dashboard === null ||
                $trip->end_dashboard === null ||
                $trip->price_fuel === null ||
                $trip->source === null ||
                $trip->destination === null ||
                $trip->trip_date === null ||
                $trip->status === null ||
                $trip->status_payment === null) {
                
                return response()->json(['error' => 'Trip Can Not be Approved, Because some important values of the Trip are Not filled yet. Thr Driver should complete filling all the required Trip Values Before it can be approved.'], 428);
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
            return TripResource::make($updatedTrip->load('order', 'driver', 'organizationUser'));
            
        });

        return $var;
    }


    /**
     * Update the specified resource in storage.
     */
    public function payTrip(PayTripRequest $request, Trip $trip)
    {
        //
        $var = DB::transaction(function () use ($request, $trip) {
            
            if ($trip->status != Trip::TRIP_STATUS_APPROVED) {
                return response()->json(['message' => 'this Trip should be APPROVED first.'], 428); 
            }

            if ($trip->status_payment == Trip::TRIP_PAID) {
                return response()->json(['message' => 'this Trip is already PAID.'], 409); 
            }

            if ($trip->order_id === null ||
                $trip->driver_id === null ||
                $trip->organization_user_id === null ||
                $trip->start_dashboard === null ||
                $trip->end_dashboard === null ||
                $trip->price_fuel === null ||
                $trip->source === null ||
                $trip->destination === null ||
                $trip->trip_date === null ||
                $trip->status === null ||
                $trip->status_payment === null) {
                
                return response()->json(['error' => 'Trip Can Not be Paid, Because some important values of the Trip are Not filled yet. Thr Driver should complete filling all the required Trip Values Before it can be Paid.'], 428);
            }
            
            // generate Unique UUID for each Invoice Trips
            $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated to be NEW and UNIQUE uuid (i.e. transaction_id_system) for Each invoice

            // create invoice for this Trip
            $invoiceTrip = InvoiceTrip::create([
                'trip_id' => $trip->id,
                'driver_id' => $trip->driver_id,
                'transaction_id_system' => $uuidTransactionIdSystem,

                'price' => $trip->price_fuel,
                'status' => InvoiceTrip::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization
                'payment_method' => $request['payment_method'],
            ]);
            //
            if (!$invoiceTrip) {
                return response()->json(['message' => 'Invoice Create Failed'], 500);
            }




            /////////// call the payment Services
            if ($request['payment_method'] == InvoiceTrip::INVOICE_BOA) {

                // $boaTripPaymentService = new BOATripPaymentService();
                // $valuePaymentRenderedView = $boaTripPaymentService->initiatePaymentForTripPR($invoiceTrip->transaction_id_system, $invoiceTrip->price, "paymentReasonValue", $trip->driver->phone_number);

                // return $valuePaymentRenderedView;
            }
            else if ($request['payment_method'] == InvoiceTrip::INVOICE_TELE_BIRR) {

                // $teleBirrTripPaymentService = new TeleBirrTripPaymentService();
                // $valuePayment = $teleBirrTripPaymentService->initiatePaymentToTrip($invoiceTrip->transaction_id_system, $invoiceTrip->price, "paymentReasonValue", $trip->driver->phone_number);

                // $updatedTrip = Trip::find($trip->id);

                // // since this condition is for the organization admin we return him the organizationUser relation
                // // return TripResource::make($updatedTrip->load('order', 'driver', 'organizationUser'));

                // return response()->json([
                //     'value_payment' => $valuePayment,
                //     'updated_trip' => TripResource::make($updatedTrip->load('order', 'driver', 'organizationUser')),
                // ]); 

            }
            else {
                return response()->json(['error' => 'Invalid payment method selected.'], 422);
            }


            
            
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
                    return response()->json(['message' => 'invalid Organization User is sent in the request. The Organization user you sent to update this trip must be EQUAL to the Organization user in the Parent Order of this trip.'], 422);
                }
            }

            // the APPROvED status of trip should NOT matter, if super_admin of adiamat (or staff of adiamat) is the one doing the Update
            // so basically since adiamat is updating the trip, we do NOT check the APPROvED status of the trip




            if (isset($request['start_dashboard'])) {
                $startDashboard = (int) $request['start_dashboard'];
            } 
            else {
                $startDashboard = (int) $trip->start_dashboard;
            }

            if (isset($request['end_dashboard'])) {
                $endDashboard = (int) $request['end_dashboard'];
            } else {
                $endDashboard = (int) $trip->end_dashboard;
            }
            

            if (isset($endDashboard) && isset($startDashboard)) {

                if ($endDashboard < $startDashboard) {
                    return response()->json(['message' => 'the end dashboard reading should NOT be less than the start dashboard reading.'], 403); 
                }

                $differenceOfDashboards = $endDashboard - $startDashboard;
                
                $contractDetail = ContractDetail::find($trip->order->contract_detail_id);

                $priceFuel = $differenceOfDashboards *((double) $contractDetail->price_fuel_payment_constant);
            }


            


            $success = $trip->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Trip Update Failed'], 500);
            }


            if (isset($priceFuel)) {

                if ($priceFuel !== null) { 
                    $trip->price_fuel = $priceFuel;
                
                    $trip->save();
                }
                
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
        $this->authorize('delete', $trip);

        $var = DB::transaction(function () use ($trip) {


            if (InvoiceTrip::where('trip_id', $trip->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the Trip because it is in use by InvoiceTrips.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the contract because it is in use by InvoiceTrips.'
                ], Response::HTTP_CONFLICT);
            }

            $trip->delete();

            return response()->json(true, 200);

        });

        return $var;
    }


    public function restore(string $id)
    {
        $trip = Trip::withTrashed()->find($id);

        $this->authorize('restore', $trip);

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
