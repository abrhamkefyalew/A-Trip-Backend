<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'country',
        'city',
        'sub_city',
        'state',
        'zip_code',
        'fax',
        'po_box',
        'relative_location',
        'latitude',
        'longitude',
        'phone_number',
        'email',
    ];

    protected $casts = [
        'relative_location' => 'array', // USED
        // 'relative_location' => 'json', // this works also
    ];

    public function addressable()
    {
        return $this->morphTo();
    }


}
