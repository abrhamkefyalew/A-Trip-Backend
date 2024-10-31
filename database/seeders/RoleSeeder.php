<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'title' => Role::SUPER_ADMIN_ROLE,
                'is_system_created' => true,
            ],
            [
                'title' => Role::MANAGER_ROLE,
                'is_system_created' => true,
            ],
            [
                'title' => Role::FINANCE_ROLE,
                'is_system_created' => true,
            ],
            [
                'title' => Role::SYSTEM_USER_ROLE,
                'is_system_created' => true,
            ],
        ];

        Role::upsert($roles, ['title']);
        
    }
}
