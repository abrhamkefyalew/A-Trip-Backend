<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceUser extends Model
{
    use HasFactory, SoftDeletes;


    




    // Invoice status constants
    public const INVOICE_STATUS_PAID = 'PAID'; // paid invoice
    public const INVOICE_STATUS_NOT_PAID = 'NOT_PAID'; // not paid invoice
    
}
