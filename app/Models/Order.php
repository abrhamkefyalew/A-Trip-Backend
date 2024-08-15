<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;
    
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_code',
        'organization_id',
        'vehicle_name_id',
        'vehicle_id',
        'driver_id',
        'with_driver',  // do we need this // because we already have it in contract_details ? , or should we use it here and remove it in contract_details
        'start_date',
        'end_date',
        'status',
        'is_terminated',
        'original_end_date',
        'pr_status',
    ];
    


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'original_end_date' => 'datetime',
    ];

    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }


    


    // make organizationInvoice as Invoice and for individual customer = IndividualCustomerInvoice
    // public function Invoices()
    // {
    //     return $this->hasMany(Invoice::class);
    // }



    // Order status constants
    public const ORDER_STATUS_PENDING = 'PENDING'; // when order is made
    public const ORDER_STATUS_SET = 'SET'; // when a driver or supplier accepts an order 

    // the below are only for order with driver
    public const ORDER_STATUS_START = 'START'; // when the driver arrives at the place where the trip starts & meets the order maker and starts to transport him
    public const ORDER_STATUS_COMPLETE = 'COMPLETE'; // when the driver takes the order maker to the destination and the order is completed


    // PR status constants
    public const ORDER_PR_STARTED = 'PR_STARTED';
    public const ORDER_PR_COMPLETED = 'PR_COMPLETED';
    public const ORDER_PR_TERMINATED = 'PR_TERMINATED';
    public const ORDER_PR_ABORTED = 'PR_ABORTED';

}
