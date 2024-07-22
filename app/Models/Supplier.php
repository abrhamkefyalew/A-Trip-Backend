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

class Supplier extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, InteractsWithMedia;

    protected $table = 'suppliers';

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

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }


    // supplier hasMany Order as a logic // but we do not define it here // Becuase we can get the suppliers orders, through his vehicles


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
    public const SUPPLIER_PROFILE_PICTURE = 'SUPPLIER_PROFILE_PICTURE';
    public const SUPPLIER_ID_PICTURE = 'SUPPLIER_ID_PICTURE';
    public const SUPPLIER_PASSPORT_PICTURE = 'SUPPLIER_PASSPORT_PICTURE';



}
