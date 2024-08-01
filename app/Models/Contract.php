<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'contracts';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_code',
        'organization_id',
        'start_date',
        'end_date',
        'is_active',
        'terminated_date',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'terminated_date' => 'datetime',
    ];



    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    public function contractDetails()
    {
        return $this->hasMany(ContractDetail::class);
    }

    // CONSTANTS

    // medias (PDF or PICTURE) // one of them
    public const ORGANIZATION_CONTRACT_FILE = 'ORGANIZATION_CONTRACT_FILE';

}
