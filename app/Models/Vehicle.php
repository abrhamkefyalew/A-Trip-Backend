<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Vehicle extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'vehicles';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_name_id',
        'supplier_id',
        'driver_id',
        'vehicle_name',
        'vehicle_description',
        'vehicle_model',
        'plate_number',
        'year',
        'is_available',
        'with_driver',
        'bank_id',
        'bank_account',
    ];



    // check if a vehicle can actually have an address and uncomment this // check abrham, ask samson
    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }


    public function vehicleName()
    {
        return $this->belongsTo(VehicleName::class);
    }
    

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    // if in any way wanted // a vehicle can have multiple contract details
    public function contractDetails()
    {
        return $this->hasMany(ContractDetail::class);
    }


    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->width(1000)
            ->height(1000);

        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150);
    }
    


    // constants

    // vehicle availability statuses
    
    public const VEHICLE_NOT_AVAILABLE = 'VEHICLE_NOT_AVAILABLE';
    public const VEHICLE_AVAILABLE = 'VEHICLE_AVAILABLE';
    public const VEHICLE_ON_TRIP = 'VEHICLE_ON_TRIP';


    // vehicle medias

    public const VEHICLE_LIBRE_PICTURE = 'VEHICLE_LIBRE_PICTURE';
    public const VEHICLE_THIRD_PERSON_PICTURE = 'VEHICLE_THIRD_PERSON_PICTURE';
    public const VEHICLE_POWER_OF_ATTORNEY_PICTURE = 'VEHICLE_POWER_OF_ATTORNEY_PICTURE';
    public const VEHICLE_PROFILE_PICTURE = 'VEHICLE_PROFILE_PICTURE';




}
