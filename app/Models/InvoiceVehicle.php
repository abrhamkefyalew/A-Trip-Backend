<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceVehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoice_vehicles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'order_user_id',
        'supplier_id',
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



    // NOTE: a SINGLE invoiceVehicle can NEVER have both order() and orderUser() (i.e. SINGLE invoice can NOT have both order_id and order_user_id set at the same time), the TWO are mutually exclusive
    //
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    //
    public function orderUser()
    {
        return $this->belongsTo(OrderUser::class);
    }





    

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
