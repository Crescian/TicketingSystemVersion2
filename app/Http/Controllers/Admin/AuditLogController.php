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

        $logs = $this->filteredQuery($request)->paginate(15)->withQueryString();

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

    // Streams the currently-filtered audit log as a CSV download.
    public function export(Request $request)
    {
        $logs = $this->filteredQuery($request)->get();

        $filename = 'audit-log-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date/Time', 'Ticket Number', 'User', 'Role', 'Action', 'Old Status', 'New Status', 'Severity', 'Notes']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->changed_at?->format('Y-m-d H:i:s'),
                    $log->ticket?->ticket_number,
                    $log->changedBy?->name ?? 'System',
                    $log->changedBy?->role?->role_name ?? '—',
                    self::getActionLabel($log->new_status ?? ''),
                    $log->old_status ?? '—',
                    $log->new_status ?? '—',
                    self::getSeverity($log->new_status ?? ''),
                    $log->notes,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // Shared filter logic for both the paginated index view and the CSV export.
    private function filteredQuery(Request $request)
    {
        $search = $request->get('search', '');
        $action = $request->get('action', '');
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

        return $query;
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