<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermissionRole extends Pivot
{
    //
    use HasFactory, SoftDeletes;

    protected $table = 'permission_role';


    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

}
