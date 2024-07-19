<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;



    public const VEHICLE_NOT_AVAILABLE = 'VEHICLE_NOT_AVAILABLE';
    public const VEHICLE_AVAILABLE = 'VEHICLE_AVAILABLE';
    public const VEHICLE_ON_TRIP = 'VEHICLE_ON_TRIP';
}
