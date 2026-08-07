<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Snapshot of the live departments table, captured for moving this project to the
// production server with its real data intact. Regenerate by re-running the
// generator script if the source data changes before the next migration.
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('departments')->insert([
            [
                'id' => '019f88f8-0725-709b-b13c-142da8fbab37',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'Management Systems and Technology',
                'created_at' => null,
            ],
            [
                'id' => '019fa7a5-79ab-716b-9813-20ef986a2d14',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Warehouse & Logistics',
                'created_at' => null,
            ],
            [
                'id' => 'ebc843f0-7a2f-40e7-8298-9821797ca666',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Accounting Services',
                'created_at' => null,
            ],
            [
                'id' => 'f112b365-a56b-4e12-9ed7-358a1b2d769b',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Exploration & Mine Geology',
                'created_at' => null,
            ],
            [
                'id' => '10802191-2791-4bb3-a3d8-1e41c3277cf5',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Legal and Regulatory Compliance',
                'created_at' => null,
            ],
            [
                'id' => '4b0f7baa-da02-4d47-9cec-c1e4ccb42186',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Mine Development, Production & Grade Control',
                'created_at' => null,
            ],
            [
                'id' => '65295819-0c69-461d-8732-15f709aaba8d',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Management Systems and Technology',
                'created_at' => null,
            ],
            [
                'id' => '4d7ee640-9a24-4416-bd91-6f87d50ab849',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'HROD',
                'created_at' => null,
            ],
            [
                'id' => 'c47b778d-a1e0-4d7c-bc3f-9778b2da2192',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Laboratory',
                'created_at' => null,
            ],
            [
                'id' => 'dc6ad486-a507-4655-9922-dfbc550d3a2f',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'HSECR',
                'created_at' => null,
            ],
            [
                'id' => 'c646ef99-f5d7-46c3-bd49-d10b14ebc32c',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Mine Engineering',
                'created_at' => null,
            ],
            [
                'id' => '9a016758-cc06-46d0-9e48-0b8ced93a6b0',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Mining and Shipment Operations',
                'created_at' => null,
            ],
            [
                'id' => '84b04bf6-f9e8-4dda-8bd0-7a04e5a23ca0',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'MEPEO',
                'created_at' => null,
            ],
            [
                'id' => '23d56708-1916-458f-b578-84ec1d6d5f55',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Office of the Resident Manager',
                'created_at' => null,
            ],
            [
                'id' => 'c91551de-20a8-4f25-8857-527fca2fa5a0',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Occupational Health & Safety',
                'created_at' => null,
            ],
            [
                'id' => 'b0628fb4-00e1-4363-8339-7ed1dff08dd1',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Mine & Port Operations',
                'created_at' => null,
            ],
            [
                'id' => '42db9f83-21f1-496c-9916-b6f10cdfd44f',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Office of the CEO',
                'created_at' => null,
            ],
            [
                'id' => 'a050cd79-c6da-46e0-b391-a7970de30f2d',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Corporate Security',
                'created_at' => null,
            ],
            [
                'id' => 'ccd2318d-1908-4e85-85b0-a62af3ef247b',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Community Relations',
                'created_at' => null,
            ],
            [
                'id' => '6160d3f7-1a00-4179-9ba9-a298d8a59946',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Fleet Operations',
                'created_at' => null,
            ],
            [
                'id' => 'dc3c5d95-238f-49ae-b19b-7cc94be684ec',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Supply Chain',
                'created_at' => null,
            ],
            [
                'id' => '187f661d-92c2-4250-b920-53ace09966bf',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Administrative Services',
                'created_at' => null,
            ],
            [
                'id' => '7071c34a-c6b1-4f91-a30b-49f25dae1f48',
                'companies_id' => '019fa7a3-b99c-73b8-a276-2008d126f018',
                'department_name' => 'Fuel Management',
                'created_at' => null,
            ],
            [
                'id' => '019fa7ad-de5e-72bf-acd4-ae0b2903f9a9',
                'companies_id' => '019fa7aa-c1d2-728e-8a37-7ce9fa60a021',
                'department_name' => 'Fleet Maintenance',
                'created_at' => null,
            ],
            [
                'id' => '019fa7ae-0387-73bb-84c0-32310ae63031',
                'companies_id' => '019fa7aa-c1d2-728e-8a37-7ce9fa60a021',
                'department_name' => 'Mine Engineering',
                'created_at' => null,
            ],
            [
                'id' => '019fa7ae-3dac-70d0-999f-ee96f6062192',
                'companies_id' => '019fa7aa-c1d2-728e-8a37-7ce9fa60a021',
                'department_name' => 'Civil Works Construction',
                'created_at' => null,
            ],
            [
                'id' => '019fa7b1-1b59-736d-aa9f-201ef77fa8b8',
                'companies_id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'department_name' => 'Legal and Regulatory Compliance',
                'created_at' => null,
            ],
            [
                'id' => '019fa7b1-4fda-7022-8ca7-0b30d8bbb1da',
                'companies_id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'department_name' => 'Technical',
                'created_at' => null,
            ],
            [
                'id' => 'e13aca81-7a39-4234-b17b-d37f4517f572',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'Finance & Treasury',
                'created_at' => null,
            ],
            [
                'id' => 'd65d3e38-0132-4ad8-80af-0cd11ffac491',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'Administrative Services',
                'created_at' => null,
            ],
            [
                'id' => '43f67fdc-3c79-4c69-8ec5-bd7c6ddd9935',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'HROD',
                'created_at' => null,
            ],
            [
                'id' => '992db843-7c75-442e-a46b-6195b8a13684',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'Accounting Services',
                'created_at' => null,
            ],
            [
                'id' => '5d2951fd-fcf6-44f5-9d9f-02f1832efedd',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'Corporate Supply Chain',
                'created_at' => null,
            ],
            [
                'id' => '57dcf907-cd0f-4ea5-9f4a-5a4a83ef5fb3',
                'companies_id' => '019f88f7-d437-701a-8763-15574685451c',
                'department_name' => 'Legal and Regulatory Compliance',
                'created_at' => null,
            ],
            [
                'id' => 'a0ee4696-9c4f-4952-9995-503abab9f0ee',
                'companies_id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'department_name' => 'Office of the CEO',
                'created_at' => null,
            ],
            [
                'id' => '930e8b64-4c18-4f0b-a998-bc88597e4f87',
                'companies_id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'department_name' => 'Executive Office',
                'created_at' => null,
            ],
            [
                'id' => 'a2bb0c7b-9be8-47f6-9b96-31d804814b47',
                'companies_id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'department_name' => 'Legal and Regulatory Compliance',
                'created_at' => null,
            ],
            [
                'id' => '146ada8d-4c10-4b23-9949-d1006f549e4d',
                'companies_id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'department_name' => 'Human Resources & Organizational Development',
                'created_at' => null,
            ],
            [
                'id' => '0669222f-55b2-405b-895c-286737285224',
                'companies_id' => '019fa7af-0e4d-7397-9f67-a789e8aa35ce',
                'department_name' => 'Management Systems and Technology',
                'created_at' => null,
            ],
            [
                'id' => '06c7835d-a608-483f-891a-d91b1a394f33',
                'companies_id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'department_name' => 'Office of the General Manager',
                'created_at' => null,
            ],
            [
                'id' => '4af08aa6-5dca-40ad-9e3b-c40184b6d727',
                'companies_id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'department_name' => 'HR & Crewing',
                'created_at' => null,
            ],
            [
                'id' => '8a3e215c-098b-4974-acd0-5f32818165f8',
                'companies_id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'department_name' => 'Procurement',
                'created_at' => null,
            ],
            [
                'id' => '853e2d08-1df3-408b-b119-c7adc0c1241f',
                'companies_id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'department_name' => 'Technical',
                'created_at' => null,
            ],
            [
                'id' => 'dd5e4f28-9a6f-404e-aec1-dea4b70f83d3',
                'companies_id' => '019fa7af-bbdc-723d-b25d-266874a0b1e7',
                'department_name' => 'Marine',
                'created_at' => null,
            ],
            [
                'id' => '019fa7b7-9407-7006-a223-87ad175cabd1',
                'companies_id' => '019fa7b7-3fec-707f-86fb-3a19cdfdc6e7',
                'department_name' => 'No Assigned Department Name',
                'created_at' => null,
            ],
            [
                'id' => '019fd5c3-e9a0-7090-b626-37bf6a7a7b39',
                'companies_id' => '019fd5bf-07ca-70eb-9938-439a12986d97',
                'department_name' => 'HROD',
                'created_at' => null,
            ],
        ]);
    }
}
