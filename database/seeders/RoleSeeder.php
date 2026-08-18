<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// IDs match the live dev database's roles table, since UserSeeder hardcodes
// these exact role_id values against real captured users.
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['id' => 'b4f15bf4-04aa-44ea-b003-f1ac3b8f736e', 'role_name' => 'Employee', 'description' => 'Submits and tracks tickets', 'level' => null],
            ['id' => '793b5da5-508c-4082-835f-d55caea045a3', 'role_name' => 'Helpdesk', 'description' => 'First level support', 'level' => 1],
            ['id' => '461560a4-101b-4bf7-9796-0ab6e35848e1', 'role_name' => 'IT Support Specialist', 'description' => 'Handles and resolves tickets', 'level' => 2],
            ['id' => '48eddbd7-37ed-48a9-bb0b-3435f70c0b2f', 'role_name' => 'Supervisor - Support Specialist', 'description' => 'Supervises IT Support Specialists', 'level' => 3],
            ['id' => '539bad4b-2ad1-40fb-b1af-2bfc10cc8865', 'role_name' => 'IT Admin', 'description' => 'Manages system and users', 'level' => 3],
            ['id' => '798a1b73-a2de-47dc-96a1-a8afb77fe589', 'role_name' => 'Supervisor - IT Admin', 'description' => 'Supervises IT Administrators', 'level' => 3],
            ['id' => 'bc8d510f-171b-447f-a674-0f4e8d251a6f', 'role_name' => 'Manager', 'description' => 'Views reports and analytics', 'level' => 4],
        ];

        foreach ($roles as &$role) {
            $role['created_at'] = $now;
            $role['updated_at'] = $now;
        }

        DB::table('roles')->insert($roles);
    }
}