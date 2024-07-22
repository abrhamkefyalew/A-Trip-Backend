<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleName extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_names';

    protected $fillable = [
        'vehicle_name',
        'vehicle_description'
    ];


    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }


    // May be do the boot function here when VehicleName is deleted

    
}
