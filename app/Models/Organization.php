<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
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
