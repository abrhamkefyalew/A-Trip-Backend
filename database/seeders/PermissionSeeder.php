<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'title' => Permission::INDEX_ADMIN,
            ],
            [
                'title' => Permission::SHOW_ADMIN,
            ],
            [
                'title' => Permission::CREATE_ADMIN,
            ],
            [
                'title' => Permission::EDIT_ADMIN,
            ],
            [
                'title' => Permission::DELETE_ADMIN,
            ],
            [
                'title' => Permission::RESTORE_ADMIN,
            ],





            [
                'title' => Permission::INDEX_BANK,
            ],
            [
                'title' => Permission::SHOW_BANK,
            ],
            [
                'title' => Permission::CREATE_BANK,
            ],
            [
                'title' => Permission::EDIT_BANK,
            ],
            [
                'title' => Permission::DELETE_BANK,
            ],
            [
                'title' => Permission::RESTORE_BANK,
            ],
            



            
            [
                'title' => Permission::INDEX_ROLE,
            ],
            [
                'title' => Permission::SHOW_ROLE,
            ],
            [
                'title' => Permission::CREATE_ROLE,
            ],
            [
                'title' => Permission::EDIT_ROLE,
            ],
            [
                'title' => Permission::DELETE_ROLE,
            ],
            [
                'title' => Permission::RESTORE_ROLE,
            ],


            [
                'title' => Permission::INDEX_PERMISSION,
            ],
            [
                'title' => Permission::SHOW_PERMISSION,
            ],


            [
                'title' => Permission::SYNC_PERMISSION_ROLE,
            ],
            [
                'title' => Permission::INDEX_PERMISSION_ROLE,
            ],
            [
                'title' => Permission::SHOW_PERMISSION_ROLE,
            ],
            [
                'title' => Permission::CREATE_PERMISSION_ROLE,
            ],
            [
                'title' => Permission::EDIT_PERMISSION_ROLE,
            ],
            [
                'title' => Permission::DELETE_PERMISSION_ROLE,
            ],
            [
                'title' => Permission::RESTORE_PERMISSION_ROLE,
            ],

            
            [
                'title' => Permission::SYNC_ADMIN_ROLE,
            ],
            [
                'title' => Permission::INDEX_ADMIN_ROLE,
            ],
            [
                'title' => Permission::SHOW_ADMIN_ROLE,
            ],
            [
                'title' => Permission::CREATE_ADMIN_ROLE,
            ],
            [
                'title' => Permission::EDIT_ADMIN_ROLE,
            ],
            [
                'title' => Permission::DELETE_ADMIN_ROLE,
            ],
            [
                'title' => Permission::RESTORE_ADMIN_ROLE,
            ],





            [
                'title' => Permission::INDEX_ORGANIZATION,
            ],
            [
                'title' => Permission::SHOW_ORGANIZATION,
            ],
            [
                'title' => Permission::CREATE_ORGANIZATION,
            ],
            [
                'title' => Permission::EDIT_ORGANIZATION,
            ],
            [
                'title' => Permission::DELETE_ORGANIZATION,
            ],
            [
                'title' => Permission::RESTORE_ORGANIZATION,
            ],



            [
                'title' => Permission::INDEX_ORGANIZATION_STAFF,
            ],
            [
                'title' => Permission::SHOW_ORGANIZATION_STAFF,
            ],
            [
                'title' => Permission::CREATE_ORGANIZATION_STAFF,
            ],
            [
                'title' => Permission::EDIT_ORGANIZATION_STAFF,
            ],
            [
                'title' => Permission::DELETE_ORGANIZATION_STAFF,
            ],
            [
                'title' => Permission::RESTORE_ORGANIZATION_STAFF,
            ],





            [
                'title' => Permission::INDEX_SUPPLIER,
            ],
            [
                'title' => Permission::SHOW_SUPPLIER,
            ],
            [
                'title' => Permission::CREATE_SUPPLIER,
            ],
            [
                'title' => Permission::EDIT_SUPPLIER,
            ],
            [
                'title' => Permission::DELETE_SUPPLIER,
            ],
            [
                'title' => Permission::RESTORE_SUPPLIER,
            ],





            [
                'title' => Permission::INDEX_VEHICLE_TYPE,
            ],
            [
                'title' => Permission::SHOW_VEHICLE_TYPE,
            ],
            [
                'title' => Permission::CREATE_VEHICLE_TYPE,
            ],
            [
                'title' => Permission::EDIT_VEHICLE_TYPE,
            ],
            [
                'title' => Permission::DELETE_VEHICLE_TYPE,
            ],
            [
                'title' => Permission::RESTORE_VEHICLE_TYPE,
            ],



            [
                'title' => Permission::INDEX_VEHICLE_NAME,
            ],
            [
                'title' => Permission::SHOW_VEHICLE_NAME,
            ],
            [
                'title' => Permission::CREATE_VEHICLE_NAME,
            ],
            [
                'title' => Permission::EDIT_VEHICLE_NAME,
            ],
            [
                'title' => Permission::DELETE_VEHICLE_NAME,
            ],
            [
                'title' => Permission::RESTORE_VEHICLE_NAME,
            ],



            [
                'title' => Permission::INDEX_VEHICLE,
            ],
            [
                'title' => Permission::SHOW_VEHICLE,
            ],
            [
                'title' => Permission::CREATE_VEHICLE,
            ],
            [
                'title' => Permission::EDIT_VEHICLE,
            ],
            [
                'title' => Permission::DELETE_VEHICLE,
            ],
            [
                'title' => Permission::RESTORE_VEHICLE,
            ],





            [
                'title' => Permission::INDEX_CONTRACT,
            ],
            [
                'title' => Permission::SHOW_CONTRACT,
            ],
            [
                'title' => Permission::CREATE_CONTRACT,
            ],
            [
                'title' => Permission::EDIT_CONTRACT,
            ],
            [
                'title' => Permission::DELETE_CONTRACT,
            ],
            [
                'title' => Permission::RESTORE_CONTRACT,
            ],





            [
                'title' => Permission::INDEX_DRIVER,
            ],
            [
                'title' => Permission::SHOW_DRIVER,
            ],
            [
                'title' => Permission::CREATE_DRIVER,
            ],
            [
                'title' => Permission::EDIT_DRIVER,
            ],
            [
                'title' => Permission::DELETE_DRIVER,
            ],
            [
                'title' => Permission::RESTORE_DRIVER,
            ],





            [
                'title' => Permission::INDEX_CUSTOMER,
            ],
            [
                'title' => Permission::SHOW_CUSTOMER,
            ],
            [
                'title' => Permission::CREATE_CUSTOMER,
            ],
            [
                'title' => Permission::EDIT_CUSTOMER,
            ],
            [
                'title' => Permission::DELETE_CUSTOMER,
            ],
            [
                'title' => Permission::RESTORE_CUSTOMER,
            ],





            [
                'title' => Permission::INDEX_ORDER,
            ],
            [
                'title' => Permission::SHOW_ORDER,
            ],
            [
                'title' => Permission::CREATE_ORDER,
            ],
            [
                'title' => Permission::EDIT_ORDER,
            ],
            [
                'title' => Permission::DELETE_ORDER,
            ],
            [
                'title' => Permission::RESTORE_ORDER,
            ],





            [
                'title' => Permission::INDEX_INVOICE,
            ],
            [
                'title' => Permission::SHOW_INVOICE,
            ],
            [
                'title' => Permission::CREATE_INVOICE,
            ],
            [
                'title' => Permission::EDIT_INVOICE,
            ],
            [
                'title' => Permission::DELETE_INVOICE,
            ],
            [
                'title' => Permission::RESTORE_INVOICE,
            ],






            [
                'title' => Permission::INDEX_BID,
            ],
            [
                'title' => Permission::SHOW_BID,
            ],
            [
                'title' => Permission::CREATE_BID,
            ],
            [
                'title' => Permission::EDIT_BID,
            ],
            [
                'title' => Permission::DELETE_BID,
            ],
            [
                'title' => Permission::RESTORE_BID,
            ],







            [
                'title' => Permission::INDEX_CONSTANT,
            ],
            [
                'title' => Permission::SHOW_CONSTANT,
            ],
            [
                'title' => Permission::CREATE_CONSTANT,
            ],
            [
                'title' => Permission::EDIT_CONSTANT,
            ],
            [
                'title' => Permission::DELETE_CONSTANT,
            ],
            [
                'title' => Permission::RESTORE_CONSTANT,
            ],





            
            [
                'title' => Permission::INDEX_TRIP,
            ],
            [
                'title' => Permission::SHOW_TRIP,
            ],
            [
                'title' => Permission::CREATE_TRIP,
            ],
            [
                'title' => Permission::EDIT_TRIP,
            ],
            [
                'title' => Permission::DELETE_TRIP,
            ],
            [
                'title' => Permission::RESTORE_TRIP,
            ],





        ];

        Permission::upsert($permissions, ['title']);

    }
}
