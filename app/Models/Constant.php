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



    /**
     * Validate model data before doing WRITE Operation
     * 
     * Override save() to validate input before saving.
     * 
     *  = > NOT recommended BECAUSE
     *                      //
     *                      - ONLY works if save() is called directly form Controller or other code parts
     *                      - will NOT work for other write functions -> i.e. create(), update(), updateOrCreate(), fill()
     * 
     */
    // public function save(array $options = [])
    // {
    //     $validator = Validator::make($this->attributes, self::$rules, self::$messages);
    
    //     if ($validator->fails()) {
    //         throw new \Exception($validator->errors()->first());
    //     }
    
    //     parent::save($options);
    // }




    /**
     * Validate model data before doing WRITE Operation
     * 
     * The "booting" method of the model.
     *
     * Registers a saving event that validates model attributes before persisting.
     * This ensures that only a validated data is written to Database during  - > save() / fill()->save() / create() / update() / updateOrCreate() operations.
     * 
     * But still NOT work for insert() and upsert() - Because these are Query Builder-level operations, bypassing Eloquent models entirely 
     * 
     * 
     *  = > RECOMMENDED BECAUSE
     *                      //
     *                      - Triggers Validation and Writing to DB during - > i.e. save(), create(), update(), updateOrCreate(), fill() operations.
     *                      - But still NOT work for insert() and upsert() - Because these are Query Builder-level operations, bypassing Eloquent models entirely, - so no events (and no validation) are triggered.
     * 
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $validator = Validator::make($model->attributesToArray(), self::getRules(), self::getMessages());

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        });
    }

    // END Define validation rules for the model, that percent value must be between 1 and 100



    // constants // for title column in constants table // initial payment percent for order_users table
    public const ORDER_USER_INITIAL_PAYMENT_PERCENT = 'ORDER_USER_INITIAL_PAYMENT_PERCENT';
    public const ORDER_USER_VEHICLE_PAYMENT_PERCENT = 'ORDER_USER_VEHICLE_PAYMENT_PERCENT';      
}
