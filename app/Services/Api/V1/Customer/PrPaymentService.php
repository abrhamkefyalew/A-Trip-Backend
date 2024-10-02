<?php

namespace App\Services\Api\V1\Customer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PrPaymentService
{
    public static function payPrs($priceAmountValue, $invoiceIdValue)
    {
        $priceAmount = (int) $priceAmountValue;
        $invoiceId = (int) $invoiceIdValue;

        // do the actual payment operation here
        // if success it will return true or success or some kind of string
        return "payment link";
    }
}