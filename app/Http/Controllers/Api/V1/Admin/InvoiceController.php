<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
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
                    $orderEndDate = Carbon::parse($order->end_date)->toDateString(); // this is used for comparison

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








    public function generateAll(Request $request) {

        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::now();   // generate bill until now
        // $endDate = Carbon::parse('2026-03-01');  // generate bill until this day


        // OUTPUT
        // From 2025-12-01 to 2025-12-31
        // From 2026-01-01 to 2026-01-31
        // From 2026-02-01 to 2026-02-28

        $enterpriseService = Order::where('payer_id', auth()->user()->id)->where('id', $request['enterprise_service_id']);

        $this->generateBill($startDate, $endDate, $enterpriseService);
        $this->updatePenalty($enterpriseService); // penalty calculation end date must always be until TODAY,     - so ALWAYS this is set automatically as NOW(),    - NO other value can NOT be set from other customer input or db input  // i have set now end_date inside the updatePenalty() function itself
        $this->getInvoices($enterpriseService); // end_date should NOT be set - we fetch all invoices including the future invoices that are generated for pre payment
    }




    ////////////////////////////////////////////////////////////////////// for SantimPay ////////////////////////////////////////////////////////////////////////////////////
    //
    /**
     * Get all full months between two dates, giving start and end date of each month.
     *
     * @param Carbon $startDate  The starting date
     * @param Carbon $endDate    The ending date    // the date in which the customer comes to the system to pay his due
     * @return Collection        A collection of arrays with start_date and end_date
     */
    public function generateBill(Carbon $startDate /* this is the SERVICE START_DATE or END_DATE of the last invoice payment of that SERVICE */ /* 2025-02-11 */ , Carbon $endDate /* 2025-02-17 */, Order $enterpriseService) /* : Collection | String */
    {


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // TODO
        //
        // Always check if start_date and end_date isset() [if they are either from REQUEST or DB TABLEs] before doing the later Operations on them,  
        // check if any column isset() from the DB TABLEs before using them or you will get ERROR
        // if the are from request check them only if the are NOT required
        //
        // 1. First check: -  if the database fetch variable is valid  (it could be Object or Collection)
        //                                                                                        if you want to continue if object is valid              if ($object) {}                                      // if object is valid do logic in if clause
        //                                                                                        if you want to continue if collection is valid          if (!$collection->isEmpty()) {}                      // if not empty do logic in if clause
        //                                                                                        //
        //                                                                                        if you want to abort if object is NOT valid             if (!$object) {ERROR}                                // if object is NOT valid ABORT      
        //                                                                                        if you want to abort if collection is NOT valid         if ($collection->isEmpty()) {ERROR}                  // if collection is empty ABORT
        //
        //
        // 2. Then DO: -  if you want to use one column value from the $object or $collection variable. check that column existence using to isset()
        //                                                                                        if (isset($object->end_date)) {}               or     if (isset($collection[x]->status)) {}                  // if object valid do logic in if clause
        //                                                                                        if (!isset($object->end_date)) {ERROR}         or     if (!isset($collection[x]->status)) {ERROR}            // if object is NOT valid ABORT
        //
        //
        // 3. if it is request variable , and it is NOT validated as "required" in Form request.  and if we may need it , also check if isset()
        //                                                                                        if (isset($request['end_date'])) {}                                                                          // if object valid do logic in if clause
        //                                                                                        if (!isset($request['end_date'])) {}                                                                         // if object is NOT valid ABORT
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        $endDate = $endDate ?? Carbon::now();
        // $endDate = Carbon::parse(NOW()); // NOT USED since Resource consuming b/c of redundancy
        

        // customer request values
        $request[] = "";

        // from policys table
        $penaltyStartsAfter = 10; // days
        //
        // ONLY one of the following columns will be created for the penalty table
        $penaltyPerDay = 20;
        $penaltyAmount = 200;

        // from customer_services table
        //
        $pricePerMonth = 1000;
        $status = $enterpriseService->status;
        $allowedToEndAtAnyDate = "true / false";
        $beginDate = "";


        // from invoice table



        // user id from TOKEN
        $customerId = "";


        // Create an empty collection to store the results, so that i can use it in while loop below
        $months = collect();

        // Start from the first day of the start month
        $current = $startDate->copy()->startOfMonth(); // 2025-02-01



        
        if ($status == "PAYMENT_NOT_STARTED") {


            // i.e. and some enterpriseServices does NOT have END date, they are infinite // Ex. - EDIR,    - Rent without contract
            // i.e. some enterpriseServices HAVE END DATE , so they are finite            // Ex. - EKUB, ,  - Rent with contract that have end date 
            //
            //
            //
            // if the end date of enterpriseService is set  (Ex. - EKUB ,   - Rent with contract that have end date)        
            if (isset($enterprizeService->end_date)) {

                $enterpriseServiceEndDate = Carbon::parse($enterpriseService->end_date);


                // the end date the user inserts OR now() is greater than the date where the Actual enterprise ends,  then we should return error
                if ($endDate->gt($enterpriseServiceEndDate)) {
                    return "the bill calculation date in which calculation will be done upto must always be less than the date the Actual service ends";
                }
            }



            if ($endDate->lte($startDate)) {
                return "ERROR : - the end date in which your invoice will be generated upto -- should be greater than the start date from your invoice will be calculated after. (i.e. the bill generation date[end date] should always be greater than service start date)";
            }


            // get the start date the customer_service first started
            // Get the first day that the customer starts to use the customer_service
            $startDateOfThisLot = $startDate; // 2025-02-11

            // Get the last day of this month (28, 29, 30, 31 are handled automatically)
            $monthEnd = $current->copy()->endOfMonth(); // 2025-02-28

            // if ($endDate < $monthEnd) {
            if ($endDate->lt($monthEnd)) {  // i will use this IF the above if does NOT compare as I expected // i.e. the above may want me to change the dates to string format so that I can compare them

                // the man is terminating the service he is using and pay the last price and stop using the service
                // but since this is the first payment he should send explicitly that he wants stop service now (and pay his last payment)
                if ($request['terminate_service_now'] != true) {
                    return "no pending payment";
                    // do  =>  break;  - or -  ($current->addMonth()) then continue; - in the while loop, if you are in while loop
                } 

                if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate == true) {
                    $monthEnd = $endDate;
                    $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next if will be respected (i.e. the next if will be resolved to be TRUE)
                } 

                if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate != true) {

                    // IF (the organization admin is the ONLY one who is allowed to terminate service from the payer) {
                    //        //  . . . . then we should return ERROR
                    // }


                    $monthEnd = $current->copy()->endOfMonth(); // 2025-02-28
                    $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next "if condition" will be respected (i.e. the next "if condition" will be resolved to be TRUE)
                } 
                
            } else if ($endDate->eq($monthEnd)) {
                if (!isset($request['terminate_service_now'])) {
                    $a = "hi";
                    // do  the same $a='hi'; - if you are in the while loop,
                }
                if ($request['terminate_service_now'] != true) {
                    $a = "hi";
                    // do  the same $a='hi'; - if you are in the while loop,
                } 
                if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate == true) {
                    $monthEnd = $endDate;
                    $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next if will be respected (i.e. the next if will be resolved to be TRUE)
                } 

                if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate != true) {

                    // IF (the organization admin is the ONLY one who is allowed to terminate service from the payer) {
                    //        //  . . . . then we should return ERROR
                    // }

                    $monthEnd = $current->copy()->endOfMonth(); // 2025-02-28
                    $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next "if condition" will be respected (i.e. the next "if condition" will be resolved to be TRUE)
                } 
            }


            // $dateDifference = $monthEnd - $startDateOfThisLot;
            $dateDifference = $monthEnd->diffInDays($startDateOfThisLot);
            $dateDifferenceFinal = $dateDifference + 1;

        
            // PRICE
            $pricePerDay = $pricePerMonth / 30 /* 30 is days of month // check abrham , john*/;
            $priceForThisLot = $pricePerDay * $dateDifferenceFinal;



            /*

                // SINCE PENALTY IS CALCULATED SEPARATELY IN ANOTHER METHOD ( this code is NOT needed here, i.e. this code is overkill )

                // PENALTY
                $penaltyPriceForThisLot = 0;
                
                $monthEndOfThisMonth_UsedToCheck_AgainstPenalty = $current->copy()->endOfMonth();
                //
                // $penaltyStartDate = $monthEndOfThisMonth_UsedToCheck_AgainstPenalty + $penaltyStartsAfter;
                $penaltyStartDate = $monthEndOfThisMonth_UsedToCheck_AgainstPenalty->copy()->addDays($penaltyStartsAfter);  // this calculation wasted resource // but there is nothing you can do it is essential for the following if
                


                // METHOD 1
                //
                // if ($endDate > $penaltyStartDate) {
                if ($endDate->gt($penaltyStartDate)) {
                    
                    if ("PENALTY_TYPE_DAILY") {

                        // $numberOfPenaltyDays = $endDate - $penaltyStartDate;
                        $numberOfPenaltyDays = $endDate->diffInDays($penaltyStartDate);
                    
                        $penaltyPriceForThisLot = $numberOfPenaltyDays * $penaltyPerDay;        // $penaltyPerDay = [ principal price / number of days in this Term (i.e. month) ] * $penalty->percent_of_principal_price

                    }

                    if ("PENALTY_TYPE_FLAT") {
                        $penaltyPriceForThisLot = $penaltyAmount;
                    }



                    
                }

                */



            
            $invoice = Invoice::create([
                'invoice_code' => Str::uuid(), // used when a payer selects multiple invoices and pays those multiple selected invoices
                'customer_id' => $customerId,
                'enterprize_id' => '$enterprize_id',
                'start_date' => $startDateOfThisLot,
                'end_date' => $monthEnd,
                'price' => $priceForThisLot,
                'penalty' => $penaltyPriceForThisLot,
                // 'number_of_penalty_days' => $numberOfPenaltyDays,
                'immune_to_penalty' => 'T / F',  // this is for all invoice tables, if this is set to T -> will be skipped during penalty calculation of NOT_PAID invoices
                'status' => "NOT_PAID", // paid / NOT_Paid   // REAL VAULE = NOT Paid, since we are only generating bill/invoice , NOT paid,    - this will be paid ONLY when the CALLBACK hits
                'paid_date' => NOW(),
            ]);



            // customer_service table (Update)
            // $enterprizeService = $enterprizeService->update([
            //     'status' => 'PAYMENT_STARTED',
            // ]);
            //
            $enterpriseService->save();


            // Otherwise, this is a full month! Save it.
            // this is just to let us see (Log)
            $months->push([
                'start_date' => $startDateOfThisLot->format('Y-m-d'), // Save as date string
                'end_date' => $monthEnd->format('Y-m-d'),     // Save as date string
            ]);

            // Move to the next month
            // $current->addMonth(); // 2025-03-01;
            
        }






        if ($status == "PAYMENT_STARTED") {


            // i.e. and some enterpriseServices does NOT have END date, they are infinite // Ex. - EDIR,    - Rent without contract
            // i.e. some enterpriseServices HAVE END DATE , so they are finite            // Ex. - EKUB, ,  - Rent with contract that have end date 
            //
            //
            //
            // if the end date of enterpriseService is set  (Ex. - EKUB ,   - Rent with contract that have end date)        
            if (isset($enterprizeService->end_date)) {

                $enterpriseServiceEndDate = Carbon::parse($enterpriseService->end_date);


                // the end date the user inserts OR now() is greater than the date where the Actual enterprise ends,  then we should return error
                if ($endDate->gt($enterpriseServiceEndDate)) {
                    return "the bill calculation date in which calculation will be done upto must always be less than the date the Actual service ends";
                }
            }


            // after the customer logs in he will get all the services (enterprize services) he is subscribed to
            //      // when he chooses one of the enterprize services, i will catch it in object named $enterprizeService
            //      //      // then i will use that $enterprizeService, in the code below,  i.e. in INVOICEs and other purposes

            $lastInvoice = $enterpriseService->invoices()->latest()->first();

            if (!$lastInvoice) {
                return "ERROR: - no valid Last invoice";
            }

            if (!$lastInvoice->end_date) {
                return "ERROR: - the last invoice has no valid End Date";
            }

            $lastInvoiceEndDate = Carbon::parse($lastInvoice->end_date); // 2025-02-28

            

            if ($endDate->lte($lastInvoiceEndDate)) {
                return "ERROR : - the end date in which your invoice will be generated upto should be greater than the start date from your invoice will be calculated after. (i.e. the bill generation date[end date] should always be greater than the last bill generation date)";
            }

            $lastInvoiceEndDateEndOfMonth = $lastInvoiceEndDate->copy()->endOfMonth(); // 2025-02-28

            if ($lastInvoiceEndDate->ne($lastInvoiceEndDateEndOfMonth)) {
                return "error, the last invoice end date must be equal to the end of the month.  i.e. the last invoice should have been paid until the end of that month, unless the enterprize service for that payer is terminated correctly, So in your case we are assuming the service you selected now is terminated";
                // or we can handle it even if the last invoice payment end date is not at the end of that month, by checking the following if 
                        // if ($lastInvoiceEndDate->ne($lastInvoiceEndDateEndOfMonth)) { 
                                // and if true = calculate the payment of the rest of the days of that month by using (the daily price that we will calculate)
                            // }

            }






            // 
            $current = $lastInvoiceEndDate->copy()->startOfMonth(); // 2025-02-01
            
            // now lets MOVE to the NEXT MONTH of the last invoice Date we get
            // $current = $current->addMonth(); // NOT USED // 2025-03-01
            $current->addMonth(); // 2025-03-01

            // Loop as long as current month start is before the end date
            while ($current->lt($endDate)) {

                // Get the first day of this month
                $monthStart = $current->copy()->startOfMonth();

                // Get the last day of this month (28, 29, 30, 31 are handled automatically)
                $monthEnd = $current->copy()->endOfMonth();




                $priceForThisLot = $pricePerMonth; /* check abrham , john */


                // if ($endDate < $monthEnd) {
                if ($endDate->lt($monthEnd)) {  // i will use this, IF the above if does NOT compare as I expected // i.e. the above may want me to change the dates to string format so that I can compare them
    
                    // the man is terminating the service he is using and pay the last price and stop using the service
                    // but since this is the first payment he should send explicitly that he wants stop service now (and pay his last payment)
                    if ($request['terminate_service_now'] != true) {
                        return "no pending payment";
                        // do  =>  break;  - or -  ($current->addMonth()) then continue; - in the while loop, if you are in while loop
                    } 
    
                    if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate == true) {
                        $monthEnd = $endDate;

                         // $dateDifference = $monthEnd - $monthStart;
                        $dateDifference = $monthEnd->diffInDays($monthStart);
                        $dateDifferenceFinal = $dateDifference + 1;

                    
                        // PRICE
                        $pricePerDay = $pricePerMonth / 30 /* 30 is days of month // check abrham , john*/;
                        $priceForThisLot = $pricePerDay * $dateDifferenceFinal;


                        $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next if will be respected (i.e. the next if will be resolved to be TRUE)
                    } 
    
                    if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate != true) {

                        // IF (the organization admin is the ONLY one who is allowed to terminate service from the payer) {
                        //        //  . . . . then we should return ERROR
                        // }


                        $monthEnd = $current->copy()->endOfMonth(); // 2025-02-28
                        $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next if will be respected (i.e. the next if will be resolved to be TRUE)
                    } 
                    
                } else if ($endDate->eq($monthEnd)) {
                    if (!isset($request['terminate_service_now'])) {
                        $a = "hi";
                        // do  the same $a='hi'; - if you are in the while loop,
                    }
                    if ($request['terminate_service_now'] != true) {
                        $a = "hi";
                        // do  the same $a='hi'; - if you are in the while loop,
                    } 
                    if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate == true) {
                        $monthEnd = $endDate;
                        $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next if will be respected (i.e. the next if will be resolved to be TRUE)
                    } 
    
                    if ($request['terminate_service_now'] == true && $allowedToEndAtAnyDate != true) {

                        // IF (the organization admin is the ONLY one who is allowed to terminate service from the payer) {
                        //        //  . . . . then we should return ERROR
                        // }


                        $monthEnd = $current->copy()->endOfMonth(); // 2025-02-28
                        $enterpriseService->status = 'PAYMENT_STARTED'; // this will ensure that the next if will be respected (i.e. the next if will be resolved to be TRUE)
                    } 
                }
    
    

                


                /*

                // SINCE PENALTY IS CALCULATED SEPARATELY IN ANOTHER METHOD ( this code is NOT needed here, i.e. this code is overkill )

                // PENALTY
                $penaltyPriceForThisLot = 0;
                
                $monthEndOfThisMonth_UsedToCheck_AgainstPenalty = $current->copy()->endOfMonth();
                //
                // $penaltyStartDate = $monthEndOfThisMonth_UsedToCheck_AgainstPenalty + $penaltyStartsAfter;
                $penaltyStartDate = $monthEndOfThisMonth_UsedToCheck_AgainstPenalty->copy()->addDays($penaltyStartsAfter);  // this calculation wasted resource // but there is nothing you can do it is essential for the following if
                


                // METHOD 1
                //
                // if ($endDate > $penaltyStartDate) {
                if ($endDate->gt($penaltyStartDate)) {
                    
                    if ("PENALTY_TYPE_DAILY") {

                        // $numberOfPenaltyDays = $endDate - $penaltyStartDate;
                        $numberOfPenaltyDays = $endDate->diffInDays($penaltyStartDate);
                    
                        $penaltyPriceForThisLot = $numberOfPenaltyDays * $penaltyPerDay;        // $penaltyPerDay = [ principal price / number of days in this Term (i.e. month) ] * $penalty->percent_of_principal_price

                    }

                    if ("PENALTY_TYPE_FLAT") {
                        $penaltyPriceForThisLot = $penaltyAmount;
                    }



                    
                }

                */


                



                $invoice = Invoice::create([
                    'invoice_code' => NULL, // initially null,   SET during payment - used when a payer selects multiple invoices and pays those multiple selected invoices // it is used for callback only, // E.x. the banks will send only one ID (i.e. invoice_code as callback)
                    'enterprise_service_id' => $enterpriseService->id,
                    'customer_id' => $customerId,
                    'enterprize_id' => '$enterprize_id',
                    'start_date' => $monthStart,
                    'end_date' => $monthEnd,
                    'price' => $priceForThisLot,
                    // 'penalty' => $penaltyPriceForThisLot,
                    // 'number_of_penalty_days' => $numberOfPenaltyDays,
                    'immune_to_penalty' => 'T / F',  // this is for all invoice tables, if this is set to T -> will be skipped during penalty calculation of NOT_PAID invoices
                    'status' => "NOT_PAID", // paid / NOT_Paid   // REAL VAULE = NOT Paid, since we are only generating bill/invoice , NOT paid,    - this will be paid ONLY when the CALLBACK hits
                    'paid_date' => NOW(),
                ]);
    
    
    
                // customer_service table (Update)
                // $enterprizeService = $enterprizeService->update([
                //     'status' => 'PAYMENT_STARTED',
                // ]);
                //
                $enterpriseService->save();
    
    







                // If the last day of this month is beyond our desired end date, we stop
                // if ($monthEnd->gt($endDate)) {
                //     break; // Exit the loop
                // }

                // Otherwise, this is a full month! Save it.
                // this is just to let us see (Log)
                $months->push([
                    'start_date' => $monthStart->format('Y-m-d'), // Save as date string
                    'end_date' => $monthEnd->format('Y-m-d'),     // Save as date string
                ]);

                // Move to the next month
                $current->addMonth();
            }

        }





        






        

        // Finally, return the collection of months
        return $months;
    }



    /**
     * Penalty will only be generated/Updated for the already generated invoices/bills ONLY (those already generaged  Bills/invoices should also be UNPAID invoices)
     * 
     * so NO new invoice/bill will be generated here
     * 
     */
    public function updatePenalty($assetUnit /*  */ )
    {
        $request[] = "";


        // ONLY one of the following columns will be created for the penalty table
        $penaltyPerDay = $assetUnit->penalty->penaltyPerDay;    // i.e. 20
        $penaltyAmountFlat = $assetUnit->penalty->penaltyAmountFlat;  // i.e. 200


        // from penalties table
        $penaltyStartsAfter = $assetUnit->penalty->penalty_starts_after; // only in days (i.e. 10 days)
        
        $status = $assetUnit->status;


        $endDate = Carbon::now();  // penalty calculation end date must always be until TODAY,     - so ALWAYS this is set automatically as NOW(),    - NO other value can NOT be set from other customer input or db input


        



        // if ($status == "PAYMENT_STARTED") {  // COMMENTED BECAUSE // THIS CONDITION is USELESS here



            $unpaidInvoices = $assetUnit->invoices()
                ->where('status', 'NOT_PAID')
                ->get();



            /*

            //
            // for the cronjob -> USE this instead
            //                          // if you are using cronjob at midnight to run the jobs, us the following commented code instead
            //
            // $unpaidInvoices = Invoice::where('status', 'NOT_PAID')->get();
            //
            // 
            //
            // foreach ($unpaidInvoices as $invoice) {
            //     $assetUnit = $invoice->assetUnit()->first();
            // }
            //

            */


            foreach ($unpaidInvoices as $invoice) {


                $invoiceEndDate = Carbon::parse($invoice->end_date);

                // PENALTY
                $penaltyPriceForThisLot = 0;
                
                //
                // $penaltyStartDate = $monthEndOfThisMonth_UsedToCheck_AgainstPenalty + $penaltyStartsAfter;
                $penaltyStartDate = $invoiceEndDate->copy()->addDays($penaltyStartsAfter);   // this calculation wasted resource // but there is nothing you can do it is essential for the following if

                // METHOD 1
                //
                // if ($endDate > $penaltyStartDate) {
                if ($endDate->gt($penaltyStartDate)) {
                    
                    if ("PENALTY_TYPE_DAILY") {

                        // $numberOfPenaltyDays = $endDate - $penaltyStartDate;
                        $numberOfPenaltyDays = $endDate->diffInDays($penaltyStartDate);
                    
                        $penaltyPriceForThisLot = $numberOfPenaltyDays * $penaltyPerDay;        // $penaltyPerDay = [ principal price / number of days in this Term (i.e. month) ] * $penalty->percent_of_principal_price

                    }

                    if ("PENALTY_TYPE_FLAT") {
                        $penaltyPriceForThisLot = $penaltyAmountFlat;
                    }



                    
                }



                
            }



            //
            // THE BELOW CODE IS USLESS - - - I THINK IT IS USELESS, DO NOT USE THE BELOW CODE I THINK
            //
            //  -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -   -

            // i.e. and some assetUnits does NOT have END date, they are infinite // Ex. - EDIR,    - Rent without contract
            // i.e. some assetUnits HAVE END DATE , so they are finite            // Ex. - EKUB, ,  - Rent with contract that have end date 
            //
            //
            //
            // if the end date of assetUnit is set  (Ex. - EKUB ,   - Rent with contract that have end date)        
            if (isset($assetUnit->end_date)) {

                $assetUnitEndDate = Carbon::parse($assetUnit->end_date);


                // the end date the user inserts OR now() is greater than the date where the Actual enterprise ends,  then we should return error
                if ($endDate->gt($assetUnitEndDate)) {
                    return "the bill calculation date in which calculation will be done upto must always be less than the date the Actual service ends";
                }
            }


            // from customer_services table

            // after the customer logs in he will get all the services (enterprize services) he is subscribed to
            //      // when he chooses one of the enterprize services, i will catch it in object named $enterprizeService
            //      //      // then i will use that $enterprizeService, in the code below,  i.e. in INVOICEs and other purposes

            $lastInvoice = $assetUnit->invoices()->latest()->first();
            $lastInvoiceEndDate = Carbon::parse($lastInvoice->end_date); // 2025-02-28

            if ($endDate->lte($lastInvoiceEndDate)) {
                return "ERROR : - the end date in which your penalty will be calculated upto should be greater than the start date from your invoice will be calculated after. (i.e. the penalty generation date[end date] should always be greater than the last bill generation date)";
            }

            $lastInvoiceEndDateEndOfMonth = $lastInvoiceEndDate->copy()->endOfMonth(); // 2025-02-28

            if ($lastInvoiceEndDate->ne($lastInvoiceEndDateEndOfMonth)) {
                return "error, the last invoice end date must be equal to the end of the month.  i.e. the last invoice should have been paid until the end of that month, unless the enterprize service for that payer is terminated correctly, So in your case we are assuming the service you selected now is terminated";
                // or we can handle it even if the last invoice payment end date is not at the end of that month, by checking the following if 
                        // if ($lastInvoiceEndDate->ne($lastInvoiceEndDateEndOfMonth)) { 
                                // and if true = calculate the payment of the rest of the days of that month by using (the daily price that we will calculate)
                            // }

            }

            // 
            $current = $lastInvoiceEndDate->copy()->startOfMonth(); // 2025-02-01
            
            // now lets MOVE to the NEXT MONTH of the last invoice Date we get
            // $current = $current->addMonth(); // NOT USED // 2025-03-01
            $current->addMonth(); // 2025-03-01




            // PENALTY
            $penaltyPriceForThisLot = 0;
                    
            $monthEndOfThisMonth_UsedToCheck_AgainstPenalty = $current->copy()->endOfMonth();
            //
            $penaltyStartDate = $monthEndOfThisMonth_UsedToCheck_AgainstPenalty + $penaltyStartsAfter; // this calculation wasted resource // but there is nothing you can do it is essential for the following if


            //
            // METHOD 1
            //
            // if ($endDate > $penaltyStartDate) {
            // if ($endDate->gt($penaltyStartDate)) {  // i will use this IF the above if does NOT compare as I expected // i.e. the above may want me to change the dates to string format so that I can compare them
            //     // $numberOfPenaltyDays = $endDate - $penaltyStartDate;
            //     $numberOfPenaltyDays = $endDate->diffInDays($penaltyStartDate);
            //
            //     $penaltyPriceForThisLot = $numberOfPenaltyDays * $penaltyPerDay;
            // }
                
        
        
        
        
        // }

        
    }


    public function getInvoices($enterprizeService) 
    {
        // end_date should NOT be set
        // bill fetch date is all, NO end date should be set, a payer could PRE generate Bill for the future if he wants to PRE PAY or just pre generate BILL   - so those bill should be shown here too ,     
        // - so we do NOT set $endDate as now() or any other value.     - getInvoices is fetch all from invoice table for that enterprise service that the customer is subscribed in



    }


    public function toCall_TheMothCalculatorFunction()
    {
        //
        // ------------------------------------
        // Example usage:

        // Define my start and end dates
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2026-03-01');

        // Call the function
        $fullMonths = $this->fullMonthStartEndIntervals($startDate, $endDate);

        // Output the results
        foreach ($fullMonths as $month) {
            echo "From {$month['start_date']} to {$month['end_date']}\n";
        }

        // OUTPUT
        // From 2025-12-01 to 2025-12-31
        // From 2026-01-01 to 2026-01-31
        // From 2026-02-01 to 2026-02-28

    }



    public function toCall_TheMothCalculatorFunction_Example2()
    {
        //
        // ------------------------------------
        // Example usage:

        // Define my start and end dates
        $startDate = Carbon::parse('2025-02-11');
        $endDate = Carbon::parse('2025-05-13');

        // Call the function
        $fullMonths = $this->fullMonthStartEndIntervals($startDate, $endDate);

        // Output the results
        foreach ($fullMonths as $month) {
            echo "From {$month['start_date']} to {$month['end_date']}\n";
        }

        // OUTPUT
        // From 2025-02-01 to 2025-02-28
        // From 2025-03-01 to 2025-03-31
        // From 2025-04-01 to 2025-04-30
    }
    //
    ////////////////////////////////////////////////////////////////////// END for SantimPay ////////////////////////////////////////////////////////////////////////////////////





    
 
}
