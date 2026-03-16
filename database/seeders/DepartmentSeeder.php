<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $company = DB::table('companies')->first();

        DB::table('departments')->insert([
            'id' => Str::uuid(),
            'companies_id' => $company->id,
            'department_name' => 'IT Support',
            'created_at' => now(),
        ]);
    }
}