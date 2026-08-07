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
        ['id' => Str::uuid(), 'role_name' => 'Employee', 'description' => 'Submits and tracks tickets', 'level' => null],
        ['id' => Str::uuid(), 'role_name' => 'Helpdesk', 'description' => 'First level support', 'level' => 1],
        ['id' => Str::uuid(), 'role_name' => 'IT Support Specialist', 'description' => 'Handles and resolves tickets', 'level' => 2],
        ['id' => Str::uuid(), 'role_name' => 'Supervisor - Support Specialist', 'description' => 'Supervises IT Support Specialists', 'level' => 3],
        ['id' => Str::uuid(), 'role_name' => 'IT Admin', 'description' => 'Manages system and users', 'level' => 3],
        ['id' => Str::uuid(), 'role_name' => 'Supervisor - IT Admin', 'description' => 'Supervises IT Administrators', 'level' => 3],
        ['id' => Str::uuid(), 'role_name' => 'Manager', 'description' => 'Views reports and analytics', 'level' => 4],
    ];

        DB::table('roles')->insert($roles);
    }
}