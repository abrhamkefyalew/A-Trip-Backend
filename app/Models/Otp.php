<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Otp extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'otps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_user_id',
        'customer_id',
        'driver_id',
        'supplier_id',
        'code',
        'expiry_time',
    ];


    public function organizationUser()
    {
        return $this->belongsTo(OrganizationUser::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


}
