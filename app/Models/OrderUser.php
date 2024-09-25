<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderUser extends Model
{
    use HasFactory, SoftDeletes;


    // Order status constants
    public const ORDER_STATUS_PENDING = 'PENDING'; // when order is made
    public const ORDER_STATUS_SET = 'SET'; // when a driver or supplier accepts an order 

    public const ORDER_STATUS_START = 'START'; // when the driver arrives at the place where the trip starts & meets the order maker and starts to transport him    or  when a vehicle departs from the supplier
    public const ORDER_STATUS_COMPLETE = 'COMPLETE'; // when the driver takes the order maker to the destination and the order is completed                         or  when the vehicle is returned back to the supplier
    
}
