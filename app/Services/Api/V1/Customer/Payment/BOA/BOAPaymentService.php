<?php

namespace App\Services\Api\V1\Customer\Payment\BOA;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class BOAPaymentService
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