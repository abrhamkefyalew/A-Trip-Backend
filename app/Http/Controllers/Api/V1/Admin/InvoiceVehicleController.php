<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Order;
use App\Models\Vehicle;
use App\Models\OrderUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\InvoiceVehicle;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\PayInvoiceVehicleRequest;
use App\Http\Resources\Api\V1\InvoiceVehicleResources\InvoiceVehicleResource;
use App\Services\Api\V1\Admin\Payment\TeleBirr\TeleBirrVehiclePaymentService;
use App\Services\Api\V1\Admin\Payment\TeleBirr\TeleBirrVehiclePaymentServiceMock;

class InvoiceVehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', InvoiceVehicle::class);

        $request->validate([
            'invoice_status_search' => [
                'sometimes', 'string', Rule::in([InvoiceVehicle::INVOICE_STATUS_NOT_PAID, InvoiceVehicle::INVOICE_STATUS_PAID]),
            ],
            // Other validation rules if needed
        ]);

        $invoiceVehicles = InvoiceVehicle::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('supplier_id_search')) {
            if (isset($request['supplier_id_search'])) {
                $supplierId = $request['supplier_id_search'];

                $invoiceVehicles = $invoiceVehicles->where('supplier_id', $supplierId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_id_search')) {
            if (isset($request['order_id_search'])) {
                $orderId = $request['order_id_search'];

                $invoiceVehicles = $invoiceVehicles->where('order_id', $orderId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_user_id_search')) {
            if (isset($request['order_user_id_search'])) {
                $orderUserId = $request['order_user_id_search'];

                $invoiceVehicles = $invoiceVehicles->where('order_user_id', $orderUserId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('invoice_status_search')) {
            if (isset($request['invoice_status_search'])) {
                $invoiceStatus = $request['invoice_status_search'];

                $invoiceVehicles = $invoiceVehicles->where('status', $invoiceStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }

        }

        

        $invoiceVehiclesData = $invoiceVehicles->with('order', 'orderUser', 'supplier')->latest()->paginate(FilteringService::getPaginate($request));

        return InvoiceVehicleResource::collection($invoiceVehiclesData);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function payInvoice(PayInvoiceVehicleRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            // todays date
            $today = now()->format('Y-m-d');


            $invoiceVehicle = InvoiceVehicle::find($request['invoice_vehicle_id']);

            if ($invoiceVehicle->order_id !== null && $invoiceVehicle->order_user_id === null) {
                // this means the invoice is from orders table (i.e. invoice is from organization order) 

                

                // Check if the associated Order exists
                if (!$invoiceVehicle->order) {
                    return response()->json(['message' => 'Related order NOT found for this invoice.'], 404);
                }


                
                // lets check the order vehicle_pr_status
                if ($invoiceVehicle->order->vehicle_pr_status === null) {
                    return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoiceVehicle->id . ' , and PR have not been started for this order yet. order: ' . $invoiceVehicle->order->id . ' , The order have vehicle_pr_status NULL.'], 500); // this scenario will NOT happen
                }
                if ($invoiceVehicle->order->vehicle_pr_status === Order::VEHICLE_PR_COMPLETED) {
                    return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoiceVehicle->id . ' , and all PR is paid for this order: ' . $invoiceVehicle->order->id . ' , The order have PR_COMPLETED status.'], 409);
                }
                if ($invoiceVehicle->order->vehicle_pr_status === Order::VEHICLE_PR_TERMINATED) {
                    return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoiceVehicle->id . ' , and this order PR is terminated for some reason. please check with the System Admin why PR is terminated. order: ' . $invoiceVehicle->order->id . ' , The order have PR_TERMINATED status.'], 410);
                }

                // check if the actual invoice is Paid // if the this invoice have status = PAID
                if ($invoiceVehicle->status === InvoiceVehicle::INVOICE_STATUS_PAID) {
                    return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoiceVehicle->id . ' , The Invoice have PAID status.'], 409);
                }
                if ($invoiceVehicle->paid_date !== null) {
                    return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoiceVehicle->id . ' , The Invoice have value in its paid date.'], 409);
                }




                $success = $invoiceVehicle->update([
                    'payment_method' => $request['payment_method'],
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'InvoiceVehicle Update Failed'], 500);
                }





                $typeOfOrder = "organization";
                /////////// call the payment Services
                if ($request['payment_method'] == InvoiceVehicle::INVOICE_BOA) {

                    // $boaVehiclePaymentService = new BOAVehiclePaymentService();
                    // $valuePaymentRenderedView = $boaVehiclePaymentService->initiatePaymentForVehiclePR($invoiceVehicle->price_amount, $invoiceVehicle->id, $typeOfOrder, $vehicle->supplier->phone_number);

                    // return $valuePaymentRenderedView;
                }
                else if ($request['payment_method'] == InvoiceVehicle::INVOICE_TELE_BIRR) {

                    $teleBirrVehiclePaymentService = new TeleBirrVehiclePaymentService();
                    $valuePayment = $teleBirrVehiclePaymentService->initiatePaymentToVehicle($invoiceVehicle->transaction_id_system, $invoiceVehicle->price_amount, "paymentReasonValue", $invoiceVehicle->supplier->phone_number);

                    $invoiceVehicleUpdated = InvoiceVehicle::find($invoiceVehicle->id);

                    return response()->json([
                        'value_payment' => $valuePayment,
                        'updated_invoice_vehicle' => InvoiceVehicleResource::make($invoiceVehicleUpdated->load('order', 'orderUser', 'supplier')),
                    ]); 

                }
                else {
                    return response()->json(['error' => 'Invalid payment method selected.'], 422);
                }

            }
            else if ($invoiceVehicle->order_id === null && $invoiceVehicle->order_user_id !== null) {
                // this means the invoice is from order_users table (i.e. invoice is from individual customer order) 



                // Check if the associated OrderUser exists
                if (!$invoiceVehicle->orderUser) {
                    return response()->json(['message' => 'Related orderUser NOT found for this invoice.'], 404);
                }



                // lets check the orderUser vehicle_pr_status
                if ($invoiceVehicle->orderUser->vehicle_pr_status === null) {
                    return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoiceVehicle->id . ' , and PR have not been started for this order yet. order: ' . $invoiceVehicle->orderUser->id . ' , The order have vehicle_pr_status NULL.'], 500); // this scenario will NOT happen
                }
                if ($invoiceVehicle->orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_COMPLETED) {
                    return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoiceVehicle->id . ' , and all PR is paid for this order: ' . $invoiceVehicle->orderUser->id . ' , The order have PR_COMPLETED status.'], 409);
                }
                if ($invoiceVehicle->orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_TERMINATED) {
                    return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoiceVehicle->id . ' , and this order PR is terminated for some reason. please check with the System Admin why PR is terminated. order: ' . $invoiceVehicle->orderUser->id . ' , The order have PR_TERMINATED status.'], 410);
                }

                // check if the actual invoice is Paid // if the this invoice have status = PAID
                if ($invoiceVehicle->status === InvoiceVehicle::INVOICE_STATUS_PAID) {
                    return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoiceVehicle->id . ' , The Invoice have PAID status.'], 409);
                }
                if ($invoiceVehicle->paid_date !== null) {
                    return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoiceVehicle->id . ' , The Invoice have value in its paid date.'], 409);
                }



                // generate Common UUID for all Organization invoices that will be paid below
                $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated OUTSIDE the FOREACH to Generate COMMON and SAME uuid (i.e. transaction_id_system) for ALL invoices that have similar invoice_code (or for all invoices to be paid in one PR payment)

                

                $success = $invoiceVehicle->update([
                    'payment_method' => $request['payment_method'],
                    'transaction_id_system' => $uuidTransactionIdSystem,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'InvoiceVehicle Update Failed'], 500);
                }




                $typeOfOrder = "individual_customer";
                /////////// call the payment Services
                if ($request['payment_method'] == InvoiceVehicle::INVOICE_BOA) {

                    // $boaVehiclePaymentService = new BOAVehiclePaymentService();
                    // $valuePaymentRenderedView = $boaVehiclePaymentService->initiatePaymentForVehiclePR($invoiceVehicle->price_amount, $invoiceVehicle->id, $typeOfOrder, $vehicle->supplier->phone_number);

                    // return $valuePaymentRenderedView;
                }
                else if ($request['payment_method'] == InvoiceVehicle::INVOICE_TELE_BIRR) {

                    $teleBirrVehiclePaymentService = new TeleBirrVehiclePaymentService();
                    $valuePayment = $teleBirrVehiclePaymentService->initiatePaymentToVehicle($invoiceVehicle->transaction_id_system, $invoiceVehicle->price_amount, "paymentReasonValue", $invoiceVehicle->supplier->phone_number);

                    $invoiceVehicleUpdated = InvoiceVehicle::find($invoiceVehicle->id);

                    return response()->json([
                        'value_payment' => $valuePayment,
                        'updated_invoice_vehicle' => InvoiceVehicleResource::make($invoiceVehicleUpdated->load('order', 'orderUser', 'supplier')),
                    ]);

                }
                else {
                    return response()->json(['error' => 'Invalid payment method selected.'], 422);
                }
                
            }
            else {
                // an invoice must have at least order_id or order_user_id, - - - -  other wise it will be the Following ERROR
                //
                return response()->json(['message' => 'This Invoice Can NOT be Processed. Because: - this InvoiceVehicle have NEITHER order_id NOR order_user_id. At least it should have ONE of the foreign ID'], 422);
            }

            

            


            


        });

        return $var;
    }


    public function testTelebirrB2C() 
    {
        $teleBirrOrganizationPaymentService = new TeleBirrVehiclePaymentService();
        $valuePayment = $teleBirrOrganizationPaymentService->initiatePaymentToVehicle((string)time() /* this must be 'transaction_id_system' because we have multiple B2C invoice tables and we want to avoid invoice_id being repeated */ , "1", "payment Reason".(string)time(), "251903942298");

        return $valuePayment; 

    }


    public function testTelebirrB2CReadReturnedXml() 
    {
        $teleBirrOrganizationPaymentService = new TeleBirrVehiclePaymentServiceMock();
        $valuePayment = $teleBirrOrganizationPaymentService->xmlReadingTest();

        return $valuePayment; 

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
    public function show(InvoiceVehicle $invoiceVehicle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InvoiceVehicle $invoiceVehicle)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceVehicle $invoiceVehicle)
    {
        //
    }
}
