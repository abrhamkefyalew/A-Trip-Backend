<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentHasManyDeep\Eloquent\Relations\Traits\HasEagerLimit;

class VehicleType extends Model
{
    use HasFactory, SoftDeletes, HasEagerLimit;
    // HasEagerLimit is used for deep relations (like permission role)

    protected $table = 'vehicle_types';

    protected $fillable = [
        'vehicle_type_name',
        'vehicle_type_description'
    ];


    public function vehicleNames()
    {
        return $this->hasMany(VehicleName::class);
    }


    // May be do the boot function here when VehicleType is deleted

    
}
