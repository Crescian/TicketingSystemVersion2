<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $businessUnit = DB::table('business_units')->first();

        DB::table('companies')->insert([
            'id' => Str::uuid(),
            'business_units_id' => $businessUnit->id,
            'company_name' => 'Main Company',
            'created_at' => now(),
        ]);
    }
}