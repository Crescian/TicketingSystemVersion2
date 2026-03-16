<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $department = DB::table('departments')->first();

        $users = [
            [
                'role_name' => 'Employee',
                'name' => 'Ruby Cabuhat',
                'email' => 'employee@example.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Helpdesk',
                'name' => 'Jasmine Leyva',
                'email' => 'helpdesk@example.com',
                'position' => 'Helpdesk Support',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Benjie Abalon',
                'email' => 'technician@example.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Noe Cagomoc',
                'email' => 'admin@example.com',
                'position' => 'IT Administrator',
            ],
            [
                'role_name' => 'Executive',
                'name' => 'Luis Leonio',
                'email' => 'executive@example.com',
                'position' => 'Chief Executive Officer',
            ],
        ];

        foreach ($users as $userData) {
            $role = DB::table('roles')
                ->where('role_name', $userData['role_name'])
                ->first();

            if (!$role) {
                $this->command->warn("Role '{$userData['role_name']}' not found. Skipping.");
                continue;
            }

            DB::table('users')->insert([
                'id' => Str::uuid(),
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'department_id' => $department->id,
                'position' => $userData['position'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}