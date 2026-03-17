<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('business_units')->insert([
            'id' => Str::uuid(),
            'business_units_name' => 'IT Department',
            'created_at' => now(),
        ]);
    }
}