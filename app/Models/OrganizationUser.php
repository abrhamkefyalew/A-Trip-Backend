<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\User as Authenticatable;

class OrganizationUser extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, InteractsWithMedia;

    protected $table = 'organization_users';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'is_active',
        'is_admin',
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
    //     $url = 'https://adiamat.com/organization-users/reset-password/?token='.$token; // modify this url // depending on your route

    //      $this->notify(new ResetPasswordNotification($url));
    // }




    // since organization_user is a minor entity i will not be needing the following now // may be uncomment later when i need them
    // public function getNameAttribute()
    // {
    //     return $this->getFullNameAttribute();
    // }

    // public function getFullNameAttribute()
    // {
    //     return $this->first_name.' '.$this->last_name;
    // }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
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
    public const ORGANIZATION_USER_PROFILE_PICTURE = 'ORGANIZATION_USER_PROFILE_PICTURE';

    
}
