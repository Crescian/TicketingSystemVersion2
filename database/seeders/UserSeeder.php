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
                'email' => 'rscabuhat@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Helpdesk',
                'name' => 'Jasmine Leyva',
                'email' => 'icthelpdesk@leoniogroup.com',
                'position' => 'Helpdesk Support',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Jose Malgapo',
                'email' => 'jfmalgapo@leoniogroup.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Benjie Abalon',
                'email' => 'bdabalon@leoniogroup.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Stephen Kyle Donida',
                'email' => 'sjdonida@leoniogroup.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Steven Tablanza',
                'email' => 'setablanza@leoniogroup.com',
                'position' => 'ICT Supervisor',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Noe Cagomoc',
                'email' => 'npcagomoc@example.com',
                'position' => 'Database Administrator',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Jev Galindez',
                'email' => 'jrgalindez@leoniogroup.com',
                'position' => 'Network Administrator',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Nia Sanchez',
                'email' => 'nmsanchez@leoniogroup.com',
                'position' => 'System Administrator',
            ],
            [
                'role_name' => 'Manager',
                'name' => 'Bon Caldito',
                'email' => 'bcaldito@example.com',
                'position' => 'ICT Manager',
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