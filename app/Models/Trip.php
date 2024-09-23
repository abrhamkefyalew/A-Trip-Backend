<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'trips';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'driver_id',
        'organization_user_id',
        'start_dashboard',
        'end_dashboard',
        'source',
        'destination',
        'trip_date',
        'trip_description',
        'status',
        'status_payment',
    ];

    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trip_date' => 'date',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function organizationUser()
    {
        return $this->belongsTo(OrganizationUser::class);
    }



    // Trip status constants // approved status constants
    public const TRIP_STATUS_APPROVED = 'APPROVED'; // paid trip // or paid log sheet
    public const TRIP_STATUS_NOT_APPROVED = 'NOT_APPROVED'; // not paid trip // or paid log sheet


    // Trip status_payment constants // payment status constants
    public const TRIP_PAID = 'PAID'; // paid trip // or paid log sheet
    public const TRIP_NOT_PAID = 'NOT_PAID'; // not paid trip // or paid log sheet



}
