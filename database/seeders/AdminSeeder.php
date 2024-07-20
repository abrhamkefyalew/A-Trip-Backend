<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $admin = Admin::firstOrCreate([
            'email' => 'adiamat@admin.com',
        ], [
            'first_name' => 'AdminAdiamatF',
            'last_name' => 'AdminAdiamatL',
            'phone_number' => '123456789',
            'password' => bcrypt('password'),
        ]);

        $role_id = Role::where('title', Role::SUPER_ADMIN_ROLE)->first('id')->id;

        $admin->roles()->sync($role_id);
    }
}
