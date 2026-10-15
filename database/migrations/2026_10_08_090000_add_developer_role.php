<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds the "Developer" role — receives system concerns sent by ICT staff (see
// App\Models\DeveloperConcern) — and moves the system developer's account onto
// it. A data migration rather than a seeder change alone, since RoleSeeder only
// runs against a fresh database and the live one already has its roles.
return new class extends Migration
{
    private const ROLE_ID = 'a7d3c2e1-5b4f-4e8a-9c6d-2f1e0b9a8d7c';
    private const EMPLOYEE_ROLE_ID = 'b4f15bf4-04aa-44ea-b003-f1ac3b8f736e';
    private const DEVELOPER_EMAIL = 'cmlanoy@leoniogroup.com';

    public function up(): void
    {
        $now = now();

        if (!DB::table('roles')->where('role_name', 'Developer')->exists()) {
            DB::table('roles')->insert([
                'id' => self::ROLE_ID,
                'role_name' => 'Developer',
                'description' => 'Receives and resolves system concerns from ICT staff',
                'level' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleId = DB::table('roles')->where('role_name', 'Developer')->value('id');

        DB::table('users')
            ->where('email', self::DEVELOPER_EMAIL)
            ->update(['role_id' => $roleId, 'updated_at' => $now]);
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'Developer')->value('id');
        if (!$roleId) {
            return;
        }

        $employeeRoleId = DB::table('roles')->where('role_name', 'Employee')->value('id') ?? self::EMPLOYEE_ROLE_ID;

        DB::table('users')->where('role_id', $roleId)->update(['role_id' => $employeeRoleId]);
        DB::table('roles')->where('id', $roleId)->delete();
    }
};
