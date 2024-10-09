<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bid extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bids';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'vehicle_id',
        'price_total',
        'price_initial',
    ];



    public function orderUser()
    {
        return $this->belongsTo(OrderUser::class);
    }


    // constants // initial payment percent
    // public const BID_ORDER_INITIAL_PAYMENT = '25';      // these can not be 0 for the moment, it means there must always be initial payment 

}
