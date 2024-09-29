<?php

namespace App\Services\Api\V1\OrganizationUser;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PrPaymentService
{
    public static function payPrs($priceAmountTotalValue, $invoiceCodeValue)
    {
        $priceAmountTotal = (int) $priceAmountTotalValue;
        $invoiceCode = $invoiceCodeValue;

        // do the actual payment operation here
        // if success it will return true or success or some kind of string
        return true;
    }
}