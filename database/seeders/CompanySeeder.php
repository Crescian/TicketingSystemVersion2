<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Snapshot of the live companies table, captured for moving this project to the
// production server with its real data intact. Regenerate by re-running the
// generator script if the source data changes before the next migration.
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('companies')->insert([
            [
                'id' => '019f88f7-d437-701a-8763-15574685451c',
                'business_units_id' => '019f88f7-1886-71d7-b6f4-88a3fc0cc511',
                'company_name' => 'Circle Corporate Inc.',
                'created_at' => null,
            ],
            [
                'id' => '019fa7b7-3fec-707f-86fb-3a19cdfdc6e7',
                'business_units_id' => '019fa7b7-0261-70f5-aac1-1fb39fbb30d8',
                'company_name' => 'No Assigned Company',
                'created_at' => null,
            ],
            [
                'id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'business_units_id' => '019fa799-be6c-708b-b228-ae28fb13d8f3',
                'company_name' => 'LNL Archipelago Minerals Inc.',
                'created_at' => null,
            ],
            [
                'id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'business_units_id' => '019f88f7-1886-71d7-b6f4-88a3fc0cc511',
                'company_name' => 'Leonio Group Corp.',
                'created_at' => null,
            ],
            [
                'id' => '019fa7aa-c1d2-728e-8a37-7ce9fa60a021',
                'business_units_id' => '019fa799-be6c-708b-b228-ae28fb13d8f3',
                'company_name' => 'Lift Logistics Resources Inc.',
                'created_at' => null,
            ],
            [
                'id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'business_units_id' => '019fd5b5-8ebd-728d-9e26-4655a22c3af7',
                'company_name' => 'Translift  Ship Management Inc.',
                'created_at' => null,
            ],
            [
                'id' => '019fd5bd-64a0-7268-801f-c230e3859be5',
                'business_units_id' => '019fd5b5-8ebd-728d-9e26-4655a22c3af7',
                'company_name' => 'Petrolift Inc.',
                'created_at' => null,
            ],
            [
                'id' => '019fd5be-b497-7319-b9df-8a1b932cf616',
                'business_units_id' => '019fd5bd-c51c-7174-b0ab-c8844d67d667',
                'company_name' => 'Leonioland Sales Inc,',
                'created_at' => null,
            ],
            [
                'id' => '019fd5bf-07ca-70eb-9938-439a12986d97',
                'business_units_id' => '019fd5bd-c51c-7174-b0ab-c8844d67d667',
                'company_name' => 'Leonioland Construction Inc.',
                'created_at' => null,
            ],
            [
                'id' => '019fd5c0-8ea2-7058-95d0-b203fcff5c2e',
                'business_units_id' => '019fd5bd-c51c-7174-b0ab-c8844d67d667',
                'company_name' => 'Leonio Land Holdings Inc.',
                'created_at' => null,
            ],
        ]);
    }
}
