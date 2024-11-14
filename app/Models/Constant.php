<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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


    // Define validation rules for the model, that percent value must be between 1 and 100
    public static $messages = [
        'percent_value.between' => 'WRONG PERCENT VALUE PASSED, The percent value must be between :min and :max.',
    ];
    
    public static $rules = [
        'percent_value' => 'required|integer|between:1,100',
    ];
    
    public function save(array $options = [])
    {
        $validator = Validator::make($this->attributes, self::$rules, self::$messages);
    
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    
        parent::save($options);
    }
    // END Define validation rules for the model, that percent value must be between 1 and 100



    // constants // initial payment percent for order_users table
    public const ORDER_USER_INITIAL_PAYMENT_PERCENT = 'ORDER_USER_INITIAL_PAYMENT_PERCENT';
    public const ORDER_USER_VEHICLE_PAYMENT_PERCENT = 'ORDER_USER_VEHICLE_PAYMENT_PERCENT';      
}
