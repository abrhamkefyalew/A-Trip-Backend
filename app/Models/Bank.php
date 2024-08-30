<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'banks';

    protected $fillable = [
        'bank_name',
        'bank_description'
    ];


    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }


    // May be do the boot function here when VehicleType is deleted
}
