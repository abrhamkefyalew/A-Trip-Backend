<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Constant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'constants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'percent_value',
    ];





    // constants // initial payment percent for order_users table
    public const ORDER_USER_INITIAL_PAYMENT_PERCENT = 'ORDER_USER_INITIAL_PAYMENT_PERCENT';      
}
