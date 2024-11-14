<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use SoftDeletes, HasRelationships;

    public $table = 'permissions';

    protected $fillable = [
        'title',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function admins()
    {
        return $this->hasManyDeepFromRelations($this->roles(), (new Role())->admins());
    }

    // no Permission Groups Yet




    // BASIC

    public const INDEX_ADMIN = 'INDEX_ADMIN';

    public const SHOW_ADMIN = 'SHOW_ADMIN';

    public const CREATE_ADMIN = 'CREATE_ADMIN';

    public const EDIT_ADMIN = 'EDIT_ADMIN';

    public const DELETE_ADMIN = 'DELETE_ADMIN';

    public const RESTORE_ADMIN = 'RESTORE_ADMIN';






    public const INDEX_BANK = 'INDEX_BANK';

    public const SHOW_BANK = 'SHOW_BANK';

    public const CREATE_BANK = 'CREATE_BANK';

    public const EDIT_BANK = 'EDIT_BANK';

    public const DELETE_BANK = 'DELETE_BANK';

    public const RESTORE_BANK = 'RESTORE_BANK';



    


    public const INDEX_ROLE = 'INDEX_ROLE';

    public const SHOW_ROLE = 'SHOW_ROLE';

    public const CREATE_ROLE = 'CREATE_ROLE';

    public const EDIT_ROLE = 'EDIT_ROLE';

    public const DELETE_ROLE = 'DELETE_ROLE';

    public const RESTORE_ROLE = 'RESTORE_ROLE';


    public const INDEX_PERMISSION = 'INDEX_PERMISSION';

    public const SHOW_PERMISSION = 'SHOW_PERMISSION';
    

    public const SYNC_PERMISSION_ROLE = 'SYNC_PERMISSION_ROLE';

    public const INDEX_PERMISSION_ROLE = 'INDEX_PERMISSION_ROLE';

    public const SHOW_PERMISSION_ROLE = 'SHOW_PERMISSION_ROLE';

    public const CREATE_PERMISSION_ROLE = 'CREATE_PERMISSION_ROLE';

    public const EDIT_PERMISSION_ROLE = 'EDIT_PERMISSION_ROLE';

    public const DELETE_PERMISSION_ROLE = 'DELETE_PERMISSION_ROLE';

    public const RESTORE_PERMISSION_ROLE = 'RESTORE_PERMISSION_ROLE';


    public const SYNC_ADMIN_ROLE = 'SYNC_ADMIN_ROLE'; // you need policy for this // abrham comment
    
    public const INDEX_ADMIN_ROLE = 'INDEX_ADMIN_ROLE';

    public const SHOW_ADMIN_ROLE = 'SHOW_ADMIN_ROLE';

    public const CREATE_ADMIN_ROLE = 'CREATE_ADMIN_ROLE';

    public const EDIT_ADMIN_ROLE = 'EDIT_ADMIN_ROLE';

    public const DELETE_ADMIN_ROLE = 'DELETE_ADMIN_ROLE';

    public const RESTORE_ADMIN_ROLE = 'DELETE_ADMIN_ROLE';











    // FUNCTIONAL

    public const INDEX_ORGANIZATION = 'INDEX_ORGANIZATION';

    public const SHOW_ORGANIZATION = 'SHOW_ORGANIZATION';

    public const CREATE_ORGANIZATION = 'CREATE_ORGANIZATION';

    public const EDIT_ORGANIZATION = 'EDIT_ORGANIZATION';

    public const DELETE_ORGANIZATION = 'DELETE_ORGANIZATION';

    public const RESTORE_ORGANIZATION = 'RESTORE_ORGANIZATION';





    public const INDEX_ORGANIZATION_STAFF = 'INDEX_ORGANIZATION_STAFF'; // These are Organization Workers   // the organization_admin is one or some of the organization_staff if he has the flag true for organization_admin in organization_users table

    public const SHOW_ORGANIZATION_STAFF = 'SHOW_ORGANIZATION_STAFF';

    public const CREATE_ORGANIZATION_STAFF = 'CREATE_ORGANIZATION_STAFF';

    public const EDIT_ORGANIZATION_STAFF = 'EDIT_ORGANIZATION_STAFF';

    public const DELETE_ORGANIZATION_STAFF = 'DELETE_ORGANIZATION_STAFF';

    public const RESTORE_ORGANIZATION_STAFF = 'RESTORE_ORGANIZATION_STAFF';





    //vehicle supplier

    public const INDEX_SUPPLIER = 'INDEX_SUPPLIER';

    public const SHOW_SUPPLIER = 'SHOW_SUPPLIER';

    public const CREATE_SUPPLIER = 'CREATE_SUPPLIER';

    public const EDIT_SUPPLIER = 'EDIT_SUPPLIER';

    public const DELETE_SUPPLIER = 'DELETE_SUPPLIER';

    public const RESTORE_SUPPLIER = 'RESTORE_SUPPLIER';





    public const INDEX_DRIVER = 'INDEX_DRIVER';

    public const SHOW_DRIVER = 'SHOW_DRIVER';

    public const CREATE_DRIVER = 'CREATE_DRIVER';

    public const EDIT_DRIVER = 'EDIT_DRIVER';

    public const DELETE_DRIVER = 'DELETE_DRIVER';

    public const RESTORE_DRIVER = 'RESTORE_DRIVER';


    


    public const INDEX_VEHICLE_TYPE = 'INDEX_VEHICLE_TYPE';

    public const SHOW_VEHICLE_TYPE = 'SHOW_VEHICLE_TYPE';

    public const CREATE_VEHICLE_TYPE = 'CREATE_VEHICLE_TYPE';

    public const EDIT_VEHICLE_TYPE = 'EDIT_VEHICLE_TYPE';

    public const DELETE_VEHICLE_TYPE = 'DELETE_VEHICLE_TYPE';

    public const RESTORE_VEHICLE_TYPE = 'RESTORE_VEHICLE_TYPE';



    public const INDEX_VEHICLE_NAME = 'INDEX_VEHICLE_NAME';

    public const SHOW_VEHICLE_NAME = 'SHOW_VEHICLE_NAME';

    public const CREATE_VEHICLE_NAME = 'CREATE_VEHICLE_NAME';

    public const EDIT_VEHICLE_NAME = 'EDIT_VEHICLE_NAME';

    public const DELETE_VEHICLE_NAME = 'DELETE_VEHICLE_NAME';

    public const RESTORE_VEHICLE_NAME = 'RESTORE_VEHICLE_NAME';



    public const INDEX_VEHICLE = 'INDEX_VEHICLE';

    public const SHOW_VEHICLE = 'SHOW_VEHICLE';

    public const CREATE_VEHICLE = 'CREATE_VEHICLE';

    public const EDIT_VEHICLE = 'EDIT_VEHICLE';

    public const DELETE_VEHICLE = 'DELETE_VEHICLE';

    public const RESTORE_VEHICLE = 'RESTORE_VEHICLE';




    
    // the contract detail permission is included under contract permission

    public const INDEX_CONTRACT = 'INDEX_CONTRACT';

    public const SHOW_CONTRACT = 'SHOW_CONTRACT';

    public const CREATE_CONTRACT = 'CREATE_CONTRACT';

    public const EDIT_CONTRACT = 'EDIT_CONTRACT';

    public const DELETE_CONTRACT = 'DELETE_CONTRACT';

    public const RESTORE_CONTRACT = 'RESTORE_CONTRACT';






    public const INDEX_CUSTOMER = 'INDEX_CUSTOMER';

    public const SHOW_CUSTOMER = 'SHOW_CUSTOMER';

    public const CREATE_CUSTOMER = 'CREATE_CUSTOMER';

    public const EDIT_CUSTOMER = 'EDIT_CUSTOMER';

    public const DELETE_CUSTOMER = 'DELETE_CUSTOMER';

    public const RESTORE_CUSTOMER = 'RESTORE_CUSTOMER';





    // these will be used in both OrganizationOrder Policy and IndividualOrder Policy  - as same 
    // (the Admin with these permissions can operate on both organization orders and individual orders)

    public const INDEX_ORDER = 'INDEX_ORDER';

    public const SHOW_ORDER = 'SHOW_ORDER';

    public const CREATE_ORDER = 'CREATE_ORDER';

    public const EDIT_ORDER = 'EDIT_ORDER';

    public const DELETE_ORDER = 'DELETE_ORDER';

    public const RESTORE_ORDER = 'RESTORE_ORDER';





    // these will be used in both OrganizationInvoice Policy and IndividualInvoice Policy  - as same 
    // (the Admin with these permissions can operate on both organization Invoices and individual Invoices)

    public const INDEX_INVOICE = 'INDEX_INVOICE';

    public const SHOW_INVOICE = 'SHOW_INVOICE';

    public const CREATE_INVOICE = 'CREATE_INVOICE';

    public const EDIT_INVOICE = 'EDIT_INVOICE';

    public const DELETE_INVOICE = 'DELETE_INVOICE';

    public const RESTORE_INVOICE = 'RESTORE_INVOICE';





    public const INDEX_BID = 'INDEX_BID';

    public const SHOW_BID = 'SHOW_BID';

    public const CREATE_BID = 'CREATE_BID';

    public const EDIT_BID = 'EDIT_BID';

    public const DELETE_BID = 'DELETE_BID';

    public const RESTORE_BID = 'RESTORE_BID';






    public const INDEX_CONSTANT = 'INDEX_CONSTANT';

    public const EDIT_CONSTANT = 'EDIT_CONSTANT';







    public const INDEX_TRIP = 'INDEX_TRIP';

    public const SHOW_TRIP = 'SHOW_TRIP';

    public const CREATE_TRIP = 'CREATE_TRIP';

    public const EDIT_TRIP = 'EDIT_TRIP';

    public const DELETE_TRIP = 'DELETE_TRIP';

    public const RESTORE_TRIP = 'RESTORE_TRIP';




}
