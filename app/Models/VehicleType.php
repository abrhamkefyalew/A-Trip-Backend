<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_types';

    protected $fillable = [
        'vehicle_type_name',
        'vehicle_type_description'
    ];


    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }


    // May be do the boot function here when VehicleType is deleted

    
}
