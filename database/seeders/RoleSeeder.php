<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => Str::uuid(), 'role_name' => 'Employee', 'description' => 'Submits and tracks tickets'],
            ['id' => Str::uuid(), 'role_name' => 'Helpdesk', 'description' => 'First level support'],
            ['id' => Str::uuid(), 'role_name' => 'IT Technician', 'description' => 'Handles and resolves tickets'],
            ['id' => Str::uuid(), 'role_name' => 'IT Admin', 'description' => 'Manages system and users'],
            ['id' => Str::uuid(), 'role_name' => 'Manager', 'description' => 'Views reports and analytics'],
        ];

        DB::table('roles')->insert($roles);
    }
}