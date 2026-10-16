<?php

namespace App\Support\Mobile;

use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Dashboard\SupervisorDashboardController as Supervisor;
use App\Http\Controllers\Helpdesk\TicketController as HelpdeskTicketController;
use App\Http\Controllers\Manager\TicketController as ManagerTicketController;
use App\Http\Controllers\Technician\TicketController as TechnicianTicketController;

// Each staff role's queue on the phone = that role's web dashboard, read
// through WebBridge::viewData(). Tabs, labels, order and count keys are copied
// from the dashboards' own tab arrays (resources/views/dashboard/*.blade.php),
// so the app's tabs and counts are always the web's.
final class StaffQueues
{
    // [controller, method, default tab, tabs: tab key => [label, count key],
    //  assignee list in the view data (for the assign/reassign pickers)]
    private const QUEUES = [
        'Helpdesk' => [
            HelpdeskTicketController::class, 'index', 'new-request', [
                'active' => ['Active', 'active'],
                'new-request' => ['For Acknowledgment', 'new_request'],
                'for-classification' => ['For Classification', 'for_classification'],
                'awaiting-supervisor' => ['Supervisor Assignment', 'awaiting_supervisor'],
                'in-progress' => ['In Progress Service Request', 'in_progress'],
                'on-hold' => ['On Hold', 'on_hold'],
                'l1-preparing-report' => ['In Progress Service Report', 'l1_preparing_report'],
                'escalated' => ['Escalated', 'escalated'],
                'pending-supervisor-approval' => ['Report For Review', 'pending_supervisor_approval'],
                'awaiting-requestor' => ['Requestor Confirmation', 'awaiting_requestor'],
                'closed' => ['Closed', 'closed'],
                'cancelled' => ['Cancelled', 'cancelled'],
            ], null,
        ],
        'IT Support Specialist' => [
            TechnicianTicketController::class, 'index', 'active', [
                'active' => ['Active Queue', 'active'],
                'awaiting-ack' => ['For Acknowledgment', 'awaiting_ack'],
                'ready-start' => ['Start Support Request', 'ready_start'],
                'in-progress' => ['In Progress Service Request', 'in_progress'],
                'on-hold' => ['On Hold', 'on_hold'],
                'preparing-report' => ['In Progress Service Report', 'preparing_report'],
                'escalated' => ['Escalated', 'escalated'],
                'report-for-review' => ['Report For Review', 'report_for_review'],
                'awaiting-closure' => ['Requestor Confirmation', 'awaiting_closure'],
                'closed' => ['Closed', 'closed'],
            ], null,
        ],
        'IT Admin' => [
            AdminTicketController::class, 'index', 'active', [
                'active' => ['Active', 'active'],
                'awaiting-ack' => ['For Acknowledgment', 'awaiting_ack'],
                'ready-start' => ['Start Admin Request', 'ready_start'],
                'in-progress' => ['In Progress Service Request', 'in_progress'],
                'on-hold' => ['On Hold', 'on_hold'],
                'in-progress-report' => ['In Progress Service Report', 'in_progress_report'],
                'escalated' => ['Escalated', 'escalated'],
                'report-for-review' => ['Report For Review', 'report_for_review'],
                'awaiting-requestor' => ['Requestor Confirmation', 'awaiting_requestor'],
                'closed' => ['Closed', 'closed'],
            ], null,
        ],
        'Supervisor - Support Specialist' => [
            Supervisor::class, 'supportIndex', 'awaiting-classification', [
                'active' => ['Active', 'active'],
                'awaiting-classification' => ['For Classification', 'awaiting_classification'],
                'awaiting-tech-ack' => ['For Acknowledgment', 'awaiting_tech_ack'],
                'ready-to-start' => ['Start Support Request', 'ready_to_start'],
                'in-progress' => ['In Progress Service Request', 'in_progress'],
                'on-hold' => ['On Hold', 'on_hold'],
                'in-progress-report' => ['In Progress Service Report', 'in_progress_report'],
                'escalated' => ['Escalated', 'escalated'],
                'pending-reclassification' => ['For Reclassification', 'pending_reclassification'],
                'pending-supervisor-approval' => ['Report For Review', 'pending_supervisor_approval'],
                'awaiting-requestor' => ['Requestor Confirmation', 'awaiting_requestor'],
                'closed' => ['Closed', 'closed'],
            ], 'technicians',
        ],
        'Supervisor - IT Admin' => [
            Supervisor::class, 'index', 'awaiting-admin-classification', [
                'active' => ['Active', 'active'],
                'awaiting-admin-supervisor' => ['For Acknowledgment', 'awaiting_admin_supervisor'],
                'awaiting-admin-classification' => ['For Classification', 'awaiting_admin_classification'],
                'awaiting-administrator-ack' => ['For IT Admin Acknowledgment', 'awaiting_administrator_ack'],
                'awaiting-administrator-sla-start' => ['Start IT Admin Request', 'awaiting_administrator_sla_start'],
                'admin-in-progress' => ['In Progress Service Request', 'admin_in_progress'],
                'admin-on-hold' => ['On Hold', 'admin_on_hold'],
                'admin-in-progress-report' => ['In Progress Service Report', 'admin_in_progress_report'],
                'pending-reclassification' => ['For Reclassification', 'pending_admin_reclassification'],
                'pending-admin-supervisor-approval' => ['Report For Review', 'pending_admin_supervisor_approval'],
                'awaiting-requestor' => ['Requestor Confirmation', 'awaiting_requestor'],
                'closed' => ['Closed', 'closed'],
            ], 'technicians',
        ],
        'Manager' => [
            ManagerTicketController::class, 'index', 'awaiting-manager', [
                'active' => ['Active', 'active'],
                'awaiting-manager' => ['Awaiting You', 'awaiting_manager'],
                'in-progress' => ['In Progress', 'in_progress'],
                'on-hold' => ['On Hold', 'on_hold'],
                'closed' => ['Closed', 'closed'],
            ], null,
        ],
    ];

    public static function for(?string $role): ?array
    {
        if (!isset(self::QUEUES[$role])) {
            return null;
        }

        [$controller, $method, $defaultTab, $tabs, $assignees] = self::QUEUES[$role];

        return compact('controller', 'method', 'defaultTab', 'tabs', 'assignees');
    }

    // The tab list with counts from the dashboard's own $counts array.
    public static function tabs(array $queue, array $counts): array
    {
        return collect($queue['tabs'])
            ->map(fn ($tab, $key) => ['key' => $key, 'label' => $tab[0], 'count' => (int) ($counts[$tab[1]] ?? 0)])
            ->values()
            ->all();
    }
}
