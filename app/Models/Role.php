<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use SoftDeletes, HasRelationships;

    public $table = 'roles';

    protected $fillable = [
        'title',
    ];

    // abrham comment // check this more
    public function admins()
    {
        return $this->belongsToMany(Admin::class)
        // ->whereNull('admin_role.deleted_at')
        ->withTimeStamps()
        ->withPivot(['expire_at', 'deleted_at']);
    }

    public function adminsWithTrashed()
    {
        return $this->belongsToMany(Admin::class)
        ->whereNotNull('admin_role.deleted_at')
        ->withTimeStamps()
        ->withPivot(['expire_at', 'deleted_at']);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    // no Permission Groups Yet

    

    // should also do this in admin model // if admin is deleted
    public static function boot()
    {
        parent::boot();

        // if role is deleted (soft deleted) , then, the corresponding data in the (Pivot tables) PermissionRole and AdminRole Should be deleted (soft deleted) also
        self::deleting(function (Role $role) {
            PermissionRole::where('role_id', $role->id)->delete();
            AdminRole::where('role_id', $role->id)->delete();
        });

        // if role is restored, then, the deleted (soft deleted) corresponding data in the (Pivot tables) PermissionRole and AdminRole Should be restored (restored from bring soft deleted) also
        self::restored(function (Role $role) {
            PermissionRole::where('role_id', $role->id)->restore();
            AdminRole::where('role_id', $role->id)->restore();
        });
    }



    public const SUPER_ADMIN_ROLE = 'SUPER_ADMIN'; // can do everything

    public const MANAGER_ROLE = 'MANAGER'; // can do everything except deleting

    public const FINANCE_ROLE = 'FINANCE'; // can do everything except deleting

    public const SYSTEM_USER_ROLE = 'SYSTEM_USER'; // can see day to day activities of the system // the lowest of the roles

    public const SYSTEM_ROLES = [self::SUPER_ADMIN_ROLE, self::MANAGER_ROLE, self::FINANCE_ROLE, self::SYSTEM_USER_ROLE];

}
