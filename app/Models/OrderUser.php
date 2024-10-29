<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_code',
        'customer_id',
        'vehicle_name_id',
        'vehicle_id',
        'driver_id',
        'supplier_id',
        'start_date',
        'begin_date',
        'end_date',
        'start_location',
        'end_location',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'status',
        'is_terminated',
        'original_end_date',
        'price_total',
        'paid_complete_status',
        'vehicle_pr_status',
        'order_description',
        'with_driver',
        'with_fuel',
        'periodic',
    ];
    


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'begin_date' => 'date',
        'end_date' => 'date',
        'original_end_date' => 'date',
    ];



    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }



    public function vehicleName()
    {
        return $this->belongsTo(VehicleName::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    
    
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    


    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    // This is individual Customer Invoice
    public function invoiceUsers()
    {
        return $this->hasMany(InvoiceUser::class);
    }





    // Order status constants
    public const ORDER_STATUS_PENDING = 'PENDING'; // when order is made
    public const ORDER_STATUS_SET = 'SET'; // when a driver or supplier accepts an order 

    public const ORDER_STATUS_START = 'START'; // when the driver arrives at the place where the trip starts & meets the order maker and starts to transport him    or  when a vehicle departs from the supplier
    public const ORDER_STATUS_COMPLETE = 'COMPLETE'; // when the driver takes the order maker to the destination and the order is completed                         or  when the vehicle is returned back to the supplier


    // System to vehicle(i.e. suppliers) payment, PR status constants
    // this status checks if the payment share of the order is paid for the vehicles (i.e. the suppliers) 
    // column = vehicle_pr_status
    // IF vehicle_pr_status is null (in the database order_users table)           // it means PR asking of that Order is not started yet
    public const VEHICLE_PR_STARTED = 'VEHICLE_PR_STARTED';                 //
    public const VEHICLE_PR_LAST = 'VEHICLE_PR_LAST';                       //
    public const VEHICLE_PR_COMPLETED = 'VEHICLE_PR_COMPLETED';             //
    public const VEHICLE_PR_TERMINATED = 'VEHICLE_PR_TERMINATED';           //
    
}
