<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractDetail extends Model
{
    use HasFactory, SoftDeletes;
    // currently this model does not need media


    protected $table = 'contract_details';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'vehicle_name_id',
        'with_driver',
        'with_fuel',
        'periodic',
        'price_contract',
        'price_vehicle_payment',
        'tax',
        'is_available',
    ];



    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
    
    public function vehicleName()
    {
        return $this->belongsTo(VehicleName::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // tax constants in percent
    public const CONTRACT_DETAIL_DEFAULT_TAX_15 = '15';

}
