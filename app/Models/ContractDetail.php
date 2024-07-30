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
    ];



    public function contracts()
    {
        return $this->belongsTo(Contract::class);
    }



}
