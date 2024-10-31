<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_code',
        'order_id',
        'organization_id',
        'transaction_id_system',
        'transaction_id_banks',
        'start_date',
        'end_date',
        'price_amount',
        'status',
        'paid_date',
        'payment_method',
        'request_payload',
        'response_payload',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paid_date' => 'date',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }






    // Invoice status constants
    public const INVOICE_STATUS_PAID = 'PAID'; // paid invoice
    public const INVOICE_STATUS_NOT_PAID = 'NOT_PAID'; // not paid invoice


    // Invoice Payment Method constants   // payment_method constants
    public const INVOICE_TELE_BIRR = 'TELE_BIRR';
    public const INVOICE_CBE_MOBILE_BANKING = 'CBE_MOBILE_BANKING';
    public const INVOICE_CBE_BIRR = 'CBE_BIRR';
    public const INVOICE_BOA = 'BOA';

}
