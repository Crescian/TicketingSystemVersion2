<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketStatusHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $action = $request->get('action', '');
        $module = $request->get('module', '');
        $severity = $request->get('severity', '');
        $date = $request->get('date', '');

        // ── Build audit log from ticket_status_histories
        // (the main source of truth for all actions in the system)
        $query = TicketStatusHistories::with([
            'ticket',
            'changedBy.role',
        ])
            ->orderByDesc('changed_at');

        // Search by user name or notes
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'ilike', "%{$search}%")
                    ->orWhereHas('changedBy', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('ticket', fn($t) =>
                        $t->where('ticket_number', 'ilike', "%{$search}%"));
            });
        }

        // Filter by action (mapped from new_status)
        if ($action) {
            $mappedStatus = match ($action) {
                'Escalate' => 'Escalated',
                'Resolve' => 'Resolved',
                default => $action
            };
            $query->where('new_status', $mappedStatus);
        }

        // Filter by date
        if ($date) {
            $query->whereDate('changed_at', $date);
        }

        // Filter by severity
        if ($severity) {
            $query->whereIn('new_status', $this->getSeverityStatuses($severity));
        }

        $logs = $query->paginate(15)->withQueryString();

        // ── Stats
        $counts = [
            'today' => TicketStatusHistories::whereDate('changed_at', today())->count(),
            'week' => TicketStatusHistories::whereBetween('changed_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'critical' => TicketStatusHistories::whereIn('new_status', ['Escalated', 'Cancelled'])
                ->count(),
            'all_time' => TicketStatusHistories::count(),
        ];

        return view('admin.audit-log', compact(
            'logs',
            'counts',
            'search',
            'action',
            'module',
            'severity',
            'date'
        ));
    }

    // Map severity to statuses
    private function getSeverityStatuses(string $severity): array
    {
        return match ($severity) {
            'critical' => ['Escalated', 'Cancelled'],
            'warning' => ['Open'],
            'info' => ['In Progress', 'Resolved'],
            default => []
        };
    }

    // Determine severity from status
    public static function getSeverity(string $status): string
    {
        return match ($status) {
            'Escalated', 'Cancelled' => 'critical',
            'Open' => 'warning',
            default => 'info',
        };
    }

    // Determine action chip type from status
    public static function getActionType(string $status): string
    {
        return match ($status) {
            'Escalated' => 'escalate',
            'Resolved' => 'resolve',
            'In Progress' => 'update',
            'Cancelled' => 'delete',
            'Open' => 'create',
            default => 'update',
        };
    }

    // Determine action label from status
    public static function getActionLabel(string $status): string
    {
        return match ($status) {
            'Escalated' => 'Escalate',
            'Resolved' => 'Resolve',
            'In Progress' => 'Update',
            'Cancelled' => 'Cancel',
            'Open' => 'Create',
            default => 'Update',
        };
    }

    // Determine action icon from status
    public static function getActionIcon(string $status): string
    {
        return match ($status) {
            'Escalated' => 'bi-exclamation-triangle',
            'Resolved' => 'bi-check-circle',
            'In Progress' => 'bi-pencil',
            'Cancelled' => 'bi-trash',
            'Open' => 'bi-plus-circle',
            default => 'bi-pencil',
        };
    }

    // Avatar class from role
    public static function getAvatarClass(?string $roleName): string
    {
        return match ($roleName) {
            'IT Admin' => 'av-admin',
            'IT Support Specialist' => 'av-tech',
            'Helpdesk' => 'av-helpdesk',
            default => 'av-employee',
        };
    }
}