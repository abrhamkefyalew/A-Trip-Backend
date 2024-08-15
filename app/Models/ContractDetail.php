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
        'with_driver', // do we need this // because we already have it in orders ? , or should we use it here and remove it in orders                                  // should we have this in here // because we already have it in orders also
        'with_fuel',
        'price',
    ];



    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
    
    public function vehicleName()
    {
        return $this->belongsTo(VehicleName::class);
    }



}
