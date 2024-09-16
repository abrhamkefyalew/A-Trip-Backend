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
        'start_date',
        'end_date',
        'price_amount',
        'status',
        'paid_date',
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
    public const INVOICE_STATUS_PAYED = 'PAYED'; // paid invoice
    public const INVOICE_STATUS_NOT_PAYED = 'NOT_PAYED'; // not payed invoice

}
