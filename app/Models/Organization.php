<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Organization extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'organizations';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'organization_description',
        'email',
        'phone_number',
        'is_active',
        'is_approved',
    ];









    

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
                'phone_number' => 'unique:organizations',
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }
    

        // Finally, the formatted or validated phone number is set back to the model's phone_number attribute
        $this->attributes['phone_number'] = $value;
    }











    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function organizationUsers()
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
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


    // do boot function , when organization is soft deleted , the organization user to be soft deleted
    // do boot function , when organization is restored , the organization user to be restored


    public const ORGANIZATION_PROFILE_PICTURE = 'ORGANIZATION_PROFILE_PICTURE';

}
