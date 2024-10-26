<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Notifications\Api\V1\ResetPasswordNotification;
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
        'password',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    // mutator function 
    // mutator functions are called automatically by laravel,       // so setPasswordAttribute is mutator function and called by laravel automatically
    // This method is a mutator that automatically processes the password value before saving it to the password attribute of the model.
    // if hashed password is sent = it will not be hashed.                     if UN-hashed password is sent = it will be hashed 
    public function setPasswordAttribute($password)
    {
        if ($password) {
            $this->attributes['password'] = app('hash')->needsRehash($password) ? Hash::make($password) : $password;
        }
    }


    public function sendPasswordResetNotification($token)
    {
        $url = 'https://adiamat.com/organization-users/reset-password/?token='.$token; // modify this url // depending on your route

         $this->notify(new ResetPasswordNotification($url));
    }


    







    

    // mutator function 
    // mutator functions are called automatically by laravel,
    // Define a mutator for the phone_number attribute
    public function setPhoneNumberAttribute($value)
    {
        if (strlen($value) == 10) {
            if ($value[0] == '0') {
                // If the number is 10 digits long and starts with '0', it replaces the leading '0' with '+251
                $ptn = "/^0/";
                $rpltxt = "+251";
                $value = preg_replace($ptn, $rpltxt, $value);
            }
            else {
                // Use Laravel's validation mechanism to return an error
                // If the number is 10 digits long but does not start with '0', 
                // it validates that it should start with '0' using Laravel's validation mechanism
                $validator = Validator::make(['phone_number' => $value], [
                    'phone_number' => 'starts_with:0', 
                ]);
    
                if ($validator->fails()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        } 
        elseif (strlen($value) == 9) {
            $value = "+251" . $value;
            
        }
        elseif (strlen($value) == 12) {
            $value = "+" . $value;
        }
        else {
            // Use Laravel's validation mechanism to return an error
            // If the number does not fall into the above scenarios (9, 10, or 12 digits), 
            // it validates the number's length against 9, 10, 12, or 13 digits using Laravel's validation mechanism.
            $validator = Validator::make(['phone_number' => $value], [
                'phone_number' => 'size:13',
            ]);
    
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }


        // check for uniqueness on the modified phone_number value after it has been processed through the formatting and validation steps
        // this condition is last to ensure that the uniqueness check is performed on the transformed and modified phone number (i.e. using the above if conditions) that will be stored in the database
        if ($this->where('phone_number', $value)->exists()) {
            // Use Laravel's validation mechanism to return an error
            $validator = Validator::make(['phone_number' => $value], [
                'phone_number' => 'unique:organization_users',
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }
    

        // Finally, the formatted or validated phone number is set back to the model's phone_number attribute
        $this->attributes['phone_number'] = $value;
    }












    // since organization_user is a minor entity i will not be needing the following now // but incase
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

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    public function trips()
    {
        return $this->hasMany(Trip::class);
    }



    // since we delete every otps of the user from otps table when login_otp and verify_otp, we shall only have one OTP code in otps table at a time
    // but after all - the nature of the relationship is hasMany()  (i.e. user has many otp)
    public function otps()
    {
        return $this->hasMany(Otp::class);
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
