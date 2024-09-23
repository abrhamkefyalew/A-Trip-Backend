<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Notifications\Api\V1\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, InteractsWithMedia;

    protected $table = 'drivers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'is_active',
        'is_approved',
        // 'is_available', // NOT NEEDED, the above is_active column is enough to decide the driver availability // DELETE THIS COLUMN
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // we are using OTP so this is commented, until further notice
    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];

    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    // we are using OTP so this is commented, until further notice
    // public function setPasswordAttribute($password)
    // {
    //     if ($password) {
    //         $this->attributes['password'] = app('hash')->needsRehash($password) ? Hash::make($password) : $password;
    //     }
    // }


    // we are using OTP so this is commented, until further notice
    // public function sendPasswordResetNotification($token)
    // {
    //     $url = 'https://adiamat.com/suppliers/reset-password/?token='.$token; // modify this url // depending on your route

    //      $this->notify(new ResetPasswordNotification($url));
    // }



    public function getNameAttribute()
    {
        return $this->getFullNameAttribute();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }


    // the driver_id should be in orders table // because the driver which conducted the order may be changed from driving that vehicle
    // so both vehicle_id and driver_id should be contained in orders table
    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    public function trips()
    {
        return $this->hasMany(Trip::class);
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
    
    public const DRIVER_LICENSE_FRONT_PICTURE = 'DRIVER_LICENSE_FRONT_PICTURE';
    public const DRIVER_LICENSE_BACK_PICTURE = 'DRIVER_LICENSE_BACK_PICTURE';
    public const DRIVER_ID_FRONT_PICTURE = 'DRIVER_ID_FRONT_PICTURE';
    public const DRIVER_ID_BACK_PICTURE = 'DRIVER_ID_BACK_PICTURE';
    public const DRIVER_PROFILE_PICTURE = 'DRIVER_PROFILE_PICTURE';



}
