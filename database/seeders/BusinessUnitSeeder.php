<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Snapshot of the live business_units table, captured for moving this project to the
// production server with its real data intact. Regenerate by re-running the
// generator script if the source data changes before the next migration.
class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('business_units')->insert([
            [
                'id' => '019f88f7-1886-71d7-b6f4-88a3fc0cc511',
                'business_units_name' => 'CSS',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => '019fa7b7-0261-70f5-aac1-1fb39fbb30d8',
                'business_units_name' => 'No Assigned BU',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => '019fd5b5-8ebd-728d-9e26-4655a22c3af7',
                'business_units_name' => 'TANKERING',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => '019fa799-be6c-708b-b228-ae28fb13d8f3',
                'business_units_name' => 'MINING',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => '019fd5bd-c51c-7174-b0ab-c8844d67d667',
                'business_units_name' => 'LAND',
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}
