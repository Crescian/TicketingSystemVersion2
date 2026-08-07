<?php

namespace Database\Seeders;

use App\Models\WorkloadClass;
use Illuminate\Database\Seeder;

class WorkloadClassSeeder extends Seeder
{
    // Targets are stored in business minutes (App\Support\BusinessClock — 9-hour
    // business day, Mon-Fri) so a resolution deadline correctly skips
    // nights/weekends instead of counting raw wall-clock time.
    public function run(): void
    {
        $classes = [
            [
                'name' => 'Quick Fix',
                'typical_application' => 'Password reset, Wi-Fi account issue, peripheral swap, licence assignment, straightforward how-to guidance',
                'response_minutes' => 15,
                'resolution_minutes' => 30,
                'response_label' => 'Within 15 Minutes',
                'resolution_label' => 'Within 30 Minutes',
                'requires_manual_resolution' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard',
                'typical_application' => 'Desktop and laptop troubleshooting, software installation, account creation, access modification, printer configuration',
                'response_minutes' => 30,
                'resolution_minutes' => 240, // 4 business hours
                'response_label' => 'Within 30 Minutes',
                'resolution_label' => 'Within 4 Business Hours',
                'requires_manual_resolution' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Complex',
                'typical_application' => 'New computer deployment, ERP issue investigation, storage and NVR concerns, report development, multi-component diagnosis',
                'response_minutes' => 60, // 1 business hour
                'resolution_minutes' => 540, // 1 business day
                'response_label' => 'Within 1 Business Hour',
                'resolution_label' => 'Within 1 Business Day',
                'requires_manual_resolution' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Major',
                'typical_application' => 'Server-level incidents, enterprise-wide outages, security incidents requiring containment and forensic review, infrastructure remediation',
                'response_minutes' => 60, // 1 business hour
                'resolution_minutes' => 2700, // 5 business days
                'response_label' => 'Within 1 Business Hour',
                'resolution_label' => 'Within 5 Business Days',
                'requires_manual_resolution' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Project / Planned Activity',
                'typical_application' => 'Deployments, migrations, rollouts, scheduled preventive maintenance, and all approved project work',
                'response_minutes' => 540, // 1 business day
                'resolution_minutes' => null,
                'response_label' => 'Within 1 Business Day',
                'resolution_label' => 'Based on Approved Project Timeline',
                'requires_manual_resolution' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Vendor Dependent',
                'typical_application' => 'Warranty claims, vendor-supported hardware repair, licence procurement, third-party system faults',
                'response_minutes' => 60, // 1 business hour (acknowledgement & escalation)
                'resolution_minutes' => null,
                'response_label' => 'Within 1 Business Hour (Acknowledgement & Escalation)',
                'resolution_label' => 'Based on Vendor Committed SLA',
                'requires_manual_resolution' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($classes as $class) {
            WorkloadClass::updateOrCreate(['name' => $class['name']], $class);
        }
    }
}
