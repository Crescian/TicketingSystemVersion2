<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Snapshot of the live sla_categories table, captured for moving this project to the
// production server with its real data intact. Regenerate by re-running the
// generator script if the source data changes before the next migration.
class SlaCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sla_categories')->insert([
            [
                'id' => '019fa69c-72a7-731f-9f12-1de2a91ebfb7',
                'name' => 'Access & Account Management',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => '2026-07-28 02:44:58',
                'updated_at' => '2026-07-28 02:44:58',
            ],
            [
                'id' => '019fa69d-1b1c-709d-9768-080f67a603a2',
                'name' => 'Hardware Support',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => '2026-07-28 02:45:41',
                'updated_at' => '2026-07-28 02:45:41',
            ],
            [
                'id' => '019fa69d-5ec5-73f7-80b5-c6060e9813ab',
                'name' => 'Software & Applications',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => '2026-07-28 02:45:59',
                'updated_at' => '2026-07-28 02:45:59',
            ],
            [
                'id' => '019fa69d-8650-72e3-9e16-2b9676b1ff10',
                'name' => 'Network & Connectivity',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => '2026-07-28 02:46:09',
                'updated_at' => '2026-07-28 02:46:09',
            ],
            [
                'id' => '019fc536-2fff-72c8-a455-2b167ecc14f4',
                'name' => 'Microsoft 365 Services',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 13,
                'created_at' => '2026-08-03 01:21:30',
                'updated_at' => '2026-08-03 01:21:30',
            ],
            [
                'id' => '019fc53c-c55a-73dd-9f66-3590f5c5a69f',
                'name' => 'Enterprise Applications',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 14,
                'created_at' => '2026-08-03 01:28:42',
                'updated_at' => '2026-08-03 01:28:42',
            ],
            [
                'id' => '019fc53e-0f67-7071-9aa5-93e8f71875d6',
                'name' => 'CCTV & Physical Security Systems',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 15,
                'created_at' => '2026-08-03 01:30:06',
                'updated_at' => '2026-08-03 01:30:06',
            ],
            [
                'id' => '019fc53e-fad2-72be-ada9-d0903e1cc0b2',
                'name' => 'Information Security',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 16,
                'created_at' => '2026-08-03 01:31:06',
                'updated_at' => '2026-08-03 01:31:06',
            ],
            [
                'id' => '019fc53f-ddfd-7044-b726-a1100160b912',
                'name' => 'General ICT Assistance',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 17,
                'created_at' => '2026-08-03 01:32:04',
                'updated_at' => '2026-08-03 01:32:04',
            ],
            [
                'id' => '019fc543-6d5f-73de-9c9f-3b1708b9759e',
                'name' => 'End-User Technical Support',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 18,
                'created_at' => '2026-08-03 01:35:58',
                'updated_at' => '2026-08-03 01:35:58',
            ],
            [
                'id' => '019fc544-920b-70c4-a541-6b7d1cfd7644',
                'name' => 'ICT Infrastructure',
                'icon' => 'bi-tag',
                'color' => '#1a4a8a',
                'is_active' => true,
                'sort_order' => 19,
                'created_at' => '2026-08-03 01:37:13',
                'updated_at' => '2026-08-03 01:37:13',
            ],
        ]);
    }
}
