<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminRole extends Pivot
{
    //
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'role_id',
        'admin_id',
    ];

    protected $table = 'admin_role';

    // is not used yet
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // is not used yet
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
}
