<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentHasManyDeep\Eloquent\Relations\Traits\HasEagerLimit;

class VehicleName extends Model
{
    use HasFactory, SoftDeletes, HasEagerLimit;
    // HasEagerLimit is used for deep relations (like permission role)

    protected $table = 'vehicle_names';

    protected $fillable = [
        'vehicle_type_id',
        'vehicle_name',
        'vehicle_description'
    ];



    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }
    

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }


    public function contractDetails()
    {
        return $this->hasMany(ContractDetail::class);
    }

    
    public function orderUsers()
    {
        return $this->hasMany(OrderUser::class);
    }

    

    // May be do the boot function here when VehicleName is deleted

    
}
