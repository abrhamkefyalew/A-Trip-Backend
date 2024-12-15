<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreInvoiceRequest;
use App\Http\Resources\Api\V1\InvoiceResources\InvoiceResource;
use App\Http\Requests\Api\V1\AdminRequests\UpdateInvoiceRequest;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        /* $validatedData = */ $request->validate([
            'invoice_status_search' => [
                'sometimes', 'string', Rule::in([Invoice::INVOICE_STATUS_NOT_PAID, Invoice::INVOICE_STATUS_PAID]),
            ],
            // Other validation rules if needed
        ]);

        $invoices = Invoice::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('organization_id_search')) {
            if (isset($request['organization_id_search'])) {
                $organizationId = $request['organization_id_search'];

                $invoices = $invoices->where('organization_id', $organizationId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_id_search')) {
            if (isset($request['order_id_search'])) {
                $orderId = $request['order_id_search'];

                $invoices = $invoices->where('order_id', $orderId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('invoice_code_search')) {
            if (isset($request['invoice_code_search'])) {
                $invoiceCode = $request['invoice_code_search'];

                $invoices = $invoices->where('invoice_code', $invoiceCode);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('invoice_status_search')) {
            if (isset($request['invoice_status_search'])) {
                $invoiceStatus = $request['invoice_status_search'];

                $invoices = $invoices->where('status', $invoiceStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }

        }

        

        $invoiceData = $invoices->with('order', 'organization')->latest()->paginate(FilteringService::getPaginate($request));

        return InvoiceResource::collection($invoiceData);
    }



    /**
     * Display a listing of the resource.
     * 
     * But Filtered by      invoice_code = invoice_code_search ,      status = NOT_PAID ,      and      paid_date = NULL
     * 
     * CAN ONLY see the UNPAID invoices of an invoice_code (NOT PAID invoices of that invoice_code)
     * 
     */
    public function indexByInvoiceCode(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);


        $invoices = Invoice::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('invoice_code_search')) {
            if (isset($request['invoice_code_search'])) {
                $invoiceCode = $request['invoice_code_search'];

                $invoices = $invoices->where('invoice_code', $invoiceCode)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    ->where('paid_date', null);

                $invoiceData = $invoices->with('order', 'organization')->latest()->get();

                // $totalPriceAmount now contains the total price_amount of all invoices with the specified 'invoice_code' , status unpaid and paid_date null // it will do add all invoices with the specified invoice_code (that are not paid and have null paid date)
                $totalPriceAmount = Invoice::where('invoice_code', $invoiceCode)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    ->where('paid_date', null)
                    ->sum('price_amount');

                return response()->json(
                    [
                        'price_amount_total' => $totalPriceAmount,
                        'invoice_code_requested_value' => $invoiceCode,
                        'data' => InvoiceResource::collection($invoiceData),
                    ],
                    200
                );
            } 
            else {
                return response()->json(['message' => 'Required parameter "invoice_code_search" is empty or Value Not Set'], 400);
            } 
        }
        else {
            return response()->json(['message' => 'Required parameter "invoice_code_search" is missing'], 400);
        } 
        
    }



    /**
     * Store a newly created resource in storage.
     * 
     * Super Admin is Asking PR from Organizations
     */
    public function store(StoreInvoiceRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            if ($request->has('invoices')) {
                
                $orderIds = collect($request->invoices)->pluck('order_id');
                // $orderIds = $request->input('invoices.*.order_id'); // check if this works

                // Check if all orders have the same organization_id            // a PR request or multiple PR request should be sent for only one organization at a time
                
                $organizationIds = Order::whereIn('id', $orderIds)->pluck('organization_id')->unique();
                if ($organizationIds->count() > 1) {
                    return response()->json(['message' => 'All orders must belong to the same organization.'], 422);
                }
                if ($organizationIds->count() < 1) {
                    return response()->json(['message' => 'no valid organization_id for the order.'], 422);
                }
                // Now we are sure all the orders in the invoice request belong to one organization
                // So let's get that one organization_id      // it is worth to mention that the following collection only have one organization_id
                // Now $organizationId contains the organization_id that can be used for insertion into subsequent tables
                $organizationId = $organizationIds->first(); // Retrieves the first organization_id FROM our collection which in fact at this stage have ONLY one organization_id  

                

                //  check if there is duplicate order_id in the JSON and if there Duplicate order_id is return ERROR
                //  i do not want similar order_id values to be sent to me in the JSON 
                //  i want ONLY = ONE invoice for ONE ORDER
                //
                if ($orderIds->count() !== $orderIds->unique()->count()) {
                    return response()->json(['message' => 'Duplicate order_id values are not allowed.'], 400);
                }
                // Continue processing the request if no duplicate order_id values are found



                // Generate a random invoice code
                $uniqueCode = Str::random(20); // Adjust the length as needed
                                                   // In Laravel's Str::random(20) function, the generated random string will consist of only letters (both uppercase and lowercase) and numbers. Special characters are NOT included in the generated string by default.
                                                   //       The generated string will be a combination of letters (A-Z and a-z) and numbers (0-9).
                                                   //
                                                   // So, if you specifically want a random string that contains only letters (both uppercase and lowercase) and numbers WITHOUT any special characters, using Str::random(20) in Laravel will meet that requirement. 
                                                   //       
                                                   // In Laravel, the Str::random(20) function generates a random string of 20 characters. The characters in the generated string can include uppercase letters (A-Z), lowercase letters (a-z), and numbers (0-9). 
                                                   //       The generated string may consist of a combination of these characters, resulting in a 20-character random alphanumeric string. 

                // Check if the generated code already exists in the database
                while (Invoice::where('invoice_code', $uniqueCode)->exists()) {
                    $uniqueCode = Str::random(20); // Regenerate the code if it already exists
                }


                
                // generate Common UUID for all Organization invoices that will be create below
                $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated OUTSIDE the FOREACH to Generate COMMON and SAME uuid (i.e. transaction_id_system) for ALL invoices that have similar invoice_code (or for all invoices created in one PR request)

                // todays date
                $today = now()->format('Y-m-d');












                // this foreach is to check for every validation and error handling BEFORE diving in to the second foreach and doing the actual operation
                // i used this approach so that      i would not ABORT in the second foreach when ERROR is found in the middle of doing the actual operation, // it is to decrease data inconsistency caused when handling errors in the second foreach
                foreach ($request->safe()->invoices as $requestData) {
                    
                    $order = Order::find($requestData['order_id']);


                    if ($order->begin_date === null) {
                        return response()->json(['message' => 'you can not ask PR for this Order. because this Order Begin Date is null, this order is not STARTED. order: ' . $order->id . ' , the order Begin Date must be set before asking PR for it.'], 428);
                    }
                    //
                    // Check if the begin_date is a valid date
                    if (strtotime($order->begin_date) === false) {
                        return response()->json(['message' => 'The Order Begin Date is not a valid date.'], 400);
                    }

                    // lets check the order actual status
                    if ($order->status === Order::ORDER_STATUS_PENDING) {
                        return response()->json(['message' => 'you can not ask PR for an order with a status PENDING. order: ' . $order->id . ' , an order must have status START or COMPLETE to be eligible for PR asking.'], 422);
                    }
                    if ($order->status === Order::ORDER_STATUS_SET) {
                        return response()->json(['message' => 'you can not ask PR for an order with a status SET. the order is only accepted and not started. order: ' . $order->id . ' , an order must have status START or COMPLETE to be eligible for PR asking.'], 422);
                    }
                    

                    // lets check the order pr_status
                    if ($order->pr_status === Order::ORDER_PR_LAST) {
                        return response()->json(['message' => 'every Available PR request have been already asked for this order: ' . $order->id . ' , The order have PR_LAST status.'], 409);
                    }
                    if ($order->pr_status === Order::ORDER_PR_COMPLETED) {
                        return response()->json(['message' => 'all PR is paid for this order: ' . $order->id . ' , The order have PR_COMPLETED status.'], 409);
                    }
                    if ($order->pr_status === Order::ORDER_PR_TERMINATED) {
                        return response()->json(['message' => 'this order PR is terminated for some reason. please check with the organization why it is terminated. order: ' . $order->id . ' , The order have PR_TERMINATED status.'], 410);
                    }


                    // invoice date // from the request
                    $invoiceRequestEndDateValue = Carbon::parse($requestData['end_date'])->toDateString();
                    // order end date // from orders table in the database
                    $orderEndDate = Carbon::parse($order->end_date)->toDateString();
                   

                    if ($invoiceRequestEndDateValue >= $today) {
                        return response()->json(['message' => 'invoice asking End date must be Less than Today.'], 400);
                    }

                    // invoice end_date from the request can NOT be greater than the order end_date. // but invoice end_date from the request can be EQUALs to the order end_date
                    if ($invoiceRequestEndDateValue > $orderEndDate) {
                        return response()->json(['message' => 'invoice end_date in the request can NOT be greater than the order end_date.    invoice asking end_date can NOT be after the date the order ends'], 400);
                    }


                    if ($order->pr_status === null) {
                        // this means the PR request we are making is for the first time (PR request made for FIRST time)
                        // in this case we include the order begin_date to calculate the price
                        // this means = (DATE DIFFERENCE + 1) // because the begin_date is entitled for payment also


                        // order begin date // from orders table in the database
                        $orderBeginDate = Carbon::parse($order->begin_date)->toDateString();
                        
                        // invoice end_date from the request can NOT be less than the order begin_date. // but invoice end_date from the request can be EQUALs to the order begin_date
                        if ($invoiceRequestEndDateValue < $orderBeginDate) {
                            return response()->json(['message' => 'invoice asking end_date in the request can NOT be less than the order begin_date.    invoice asking end_date can NOT be before the date the order begins'], 400);
                        }


                    }
                    else if ($order->pr_status === Order::ORDER_PR_STARTED && Invoice::where('order_id', $order->id)->exists()) {
                        // this means the PR request we are making is NOT for the first time (PR request made for SUBSEQUENT time)
                        // in this case we does NOT include the last invoice end_date to calculate the price BECAUSE that date is considered in price calculation in the last invoice
                        // this means only = (DATE DIFFERENCE) // only the actual subtraction will be used


                        $unPaidOrderInvoiceExist = Invoice::where('order_id', $order->id)
                                        ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                                        ->exists();
                        
                        if ($unPaidOrderInvoiceExist) {
                            return response()->json([
                                'message' => 'there is NOT_PAID invoice in invoices table with this order, you need to ask the organization to pay the previous PR for this particular order before asking another PR for this particular Order: ' . $order->id,
                                'order_id' => $order->id,
                                'order_vehicle_plate_number' => $order->vehicle->plate_number
                            ], 428);
                        }


                        // lets get the last invoice asked with this order_id
                        $lastInvoice = $order->invoices()->latest()->first();

                        if (!$lastInvoice) {
                            return response()->json(['message' => 'The last invoice asked for this order can not be found. This Invoice Can NOT be Processed'], 500);
                        }


                        // the last asked invoice end_date of this order // from invoices table in the database
                        $lastInvoiceEndDate = Carbon::parse($lastInvoice->end_date)->toDateString();

                        // invoice end_date from the request can NOT be Less than or Equals to the last invoice end_date. // invoice end_date from the request should always be greater than the last invoice end_date
                        if ($invoiceRequestEndDateValue <= $lastInvoiceEndDate) {
                            return response()->json(['message' => 'invoice end_date in the request can NOT be Less than or Equal to the last asked invoice (end_date) of this order.    invoice end_date from the request should always be greater than the last asked invoice (end_date) of this order'], 422);
                        }
                        


                    }
                    else {
                        return response()->json(['message' => 'This Invoice Can NOT be Processed'], 422);
                    }



                }







                $invoiceIds = [];

                // Now We are sure all the impurities are filtered in the above foreach
                // So do the ACTUAL Operations on each of the invoices sent in the request
                foreach ($request->safe()->invoices as $requestData) {
                    
                    $order = Order::find($requestData['order_id']);


                    // invoice date // from the request
                    $invoiceRequestEndDate = Carbon::parse($requestData['end_date']); // because we need this for calculation we removed the toDateString
                    $invoiceRequestEndDateStringVersion = Carbon::parse($requestData['end_date'])->toDateString(); // this is used for comparison in the following if condition
                    // order end date // from orders table in the database
                    $orderEndDate = Carbon::parse($order->end_date)->toDateString();

                    // get the daily price of the order vehicle_name_id from contract_details_table;
                    $orderPricePerDay = $order->contractDetail->price_contract;

                    
                    if ($invoiceRequestEndDateStringVersion < $orderEndDate) {
                        $prStatus = Order::ORDER_PR_STARTED;
                    }
                    if ($invoiceRequestEndDateStringVersion === $orderEndDate) {
                        $prStatus = Order::ORDER_PR_LAST;
                    }
                    

                    
                    if ($order->pr_status === null) {
                        // this means the PR request we are making is for the first time (PR request made for FIRST time)
                        // in this case we include the order begin_date to calculate the price
                        // this means = (DATE DIFFERENCE + 1) // because the begin_date is entitled for payment also

                        $invoiceStartDate = Carbon::parse($order->begin_date); // because we need this for calculation we removed the toDateString

                        // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
                        // It does not assume all months have a fixed number of days like 30 days.
                        $differenceInDays = $invoiceRequestEndDate->diffInDays($invoiceStartDate);

                        // this means = (DATE DIFFERENCE + 1) // because the order begin_date is entitled for payment also
                        $differenceInDaysPlusBeginDate = $differenceInDays + 1;
                        
                        $invoicePriceAmountOfAllAskedDays = $differenceInDaysPlusBeginDate * $orderPricePerDay;



                    }
                    else if ($order->pr_status === Order::ORDER_PR_STARTED && Invoice::where('order_id', $order->id)->exists()) {
                        // this means the PR request we are making is NOT for the first time (PR request made for SUBSEQUENT time)
                        // in this case we does NOT include the last invoice end_date to calculate the price BECAUSE that date is considered in price calculation in the last invoice
                        // this means only = (DATE DIFFERENCE) // only the actual subtraction will be used


                        // lets get the last invoice asked with this order_id
                        $lastInvoice = $order->invoices()->latest()->first();

                        if (!$lastInvoice) {
                            return response()->json(['message' => 'The last invoice asked for this order can not be found. This Invoice Can NOT be Processed'], 500);
                        }


                        // the last asked invoice end_date of this order // from invoices table in the database
                        $invoiceStartDate = Carbon::parse($lastInvoice->end_date); // because we need this for calculation we removed the toDateString

                        // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
                        // It does not assume all months have a fixed number of days like 30 days.
                        $differenceInDays = $invoiceRequestEndDate->diffInDays($invoiceStartDate);

                        $invoicePriceAmountOfAllAskedDays = $differenceInDays * $orderPricePerDay;


                    }
                    else {
                        return response()->json(['message' => 'This Invoice Can NOT be Processed'], 422);
                    }

                    

                    $invoice = Invoice::create([
                        'invoice_code' => $uniqueCode,

                        'order_id' => $requestData['order_id'],
                        'organization_id' => $organizationId,

                        'transaction_id_system' => $uuidTransactionIdSystem,

                        'start_date' => $invoiceStartDate,
                        'end_date' => $requestData['end_date'],

                        'price_amount' => $invoicePriceAmountOfAllAskedDays,
                        'status' => Invoice::INVOICE_STATUS_NOT_PAID,
                        'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization                                                                                  
                    ]);
                    //
                    if (!$invoice) {
                        return response()->json(['message' => 'Invoice Create Failed'], 500);
                    }


                    $success = $order->update([
                        'pr_status' => $prStatus,
                    ]);
                    //
                    if (!$success) {
                        return response()->json(['message' => 'Order Update Failed'], 500);
                    }

                    $invoiceIds[] = $invoice->id;

                }


                

                // this get the invoices created from the above two if conditions 
                $invoicesData = Invoice::whereIn('id', $invoiceIds)->with('order', 'organization')->latest()->get();   
                return InvoiceResource::collection($invoicesData);
                
            }
            
            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
