<?php

return [
    'payment' => [

        // Adiamat is Getting Paid
        'customer_to_business' => [
            'organization_pr' => env('PAYMENT_ORGANIZATION_PR', 'OPR-'),                            // (organization PR) payment
            //
            'individual_customer_initial' => env('PAYMENT_INDIVIDUAL_CUSTOMER_INITIAL', 'ICI-'),    // (individual customer initial) payment
            'individual_customer_final' => env('PAYMENT_INDIVIDUAL_CUSTOMER_FINAL', 'ICF-'),        // (individual customer final) payment
        ],


        // Adiamat is Paying
        'business_to_customer' => [
            'vehicle_of_order' => env('PAYMENT_VEHICLE_OF_ORDER', 'VOO-'),                          // (vehicle of Order) payment 
            'driver_trip_fuel' => env('PAYMENT_DRIVER_TRIP_FUEL', 'DTF-'),                          // (Driver Trip Fuel) payment
        ],
        

    ],



    // DEMO CONSTANTS
    'others_one' => [
        'constant_one' => env('CONSTANT_ONE', 'my_constant_one_here'),
        'constant_two' => env('CONSTANT_TWO', 'my_constant_two_here'),
    ],

    'secrets' => [
        'token' => env('SECRETS_TOKEN', 'my_secrets_token_here'),
        'url' => env('SECRETS_URL', 'https://api.secrets.com'),
    ],
    // end DEMO CONSTANTS

];

// access them as i.e. // config('constants.payment.customer_to_business.organization_pr')

    /**
     * These are Constants for payments 
     * They will be APPENDED on ID of invoices as PREFIX, before the those invoice IDs are sent to the banks
     * //
     * // DURING CALL Backs From BANKs,
     * //       - they send us back those PREFIXed invoice IDs
     * //       - we use those PREFIXEs to identify which user type owens that invoice ID
     * //       - so FIRST we go to that "user type invoice table" THEN we do confirmation on the payment of that invoice id
     * 
     * 
     * 
     * from customer or organization to adiamat
     *      //
     *      "OPR-" = (organization PR) payment
     *      //
     *      "ICI-" = (individual customer initial) payment
     *      "ICF-" = (individual customer final) payment
     * 
     * from adiamat to others payment
     *      // NOTE : - 
     *      //
     *      "VOO-" = (vehicle of Order) payment
     *      //
     *      "DTF-" = (Driver Trip Fuel) payment
     * 
     * 
     * 
     */