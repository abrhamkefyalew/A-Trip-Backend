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
        'contract_detail_id',
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
        'pr_status',
        'order_description',
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

    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contractDetail()
    {
        return $this->belongsTo(ContractDetail::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleName()
    {
        return $this->belongsTo(VehicleName::class);
    }

    
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }


    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    


    // This is Organization Invoice
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }




    // Order status constants
    public const ORDER_STATUS_PENDING = 'PENDING'; // when order is made
    public const ORDER_STATUS_SET = 'SET'; // when a driver or supplier accepts an order 

    public const ORDER_STATUS_START = 'START'; // when the driver arrives at the place where the trip starts & meets the order maker and starts to transport him    or  when a vehicle departs from the supplier
    public const ORDER_STATUS_COMPLETE = 'COMPLETE'; // when the driver takes the order maker to the destination and the order is completed                         or  when the vehicle is returned back to the supplier


    // PR status constants
    public const ORDER_PR_STARTED = 'PR_STARTED';
    public const ORDER_PR_LAST = 'PR_LAST'; // IF pr asking for an Order is completed // but not paid yet
    public const ORDER_PR_COMPLETED = 'PR_COMPLETED'; // when all of the PR is paid in full by the organization for the order we CLOSE it using this constant
    public const ORDER_PR_TERMINATED = 'PR_TERMINATED'; // in any case if the pr payment is terminated
    

}
