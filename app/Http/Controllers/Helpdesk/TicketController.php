<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketResolvedMail;
use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\TicketStatusHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->filled('status')
            ? $request->get('status')
            : 'new-request';
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'New Request'        THEN 1
                WHEN status = 'Awaiting Supervisor' THEN 2
                WHEN status = 'In Progress'   THEN 3
                WHEN status = 'Escalated'    THEN 4
                WHEN status = 'Pending Closure'    THEN 5
                WHEN status = 'Awaiting Requestor'    THEN 6
                WHEN status = 'Closed'    THEN 7
                ELSE 8 END")
            ->orderByDesc('created_at');

        // 'active' = everything still open, before it lands on Closed
        $activeStatuses = [
            'New Request',
            'Awaiting Supervisor',
            'In Progress',
            'Escalated',
            'Pending Closure',
            'Awaiting Requestor',
        ];

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } elseif ($status !== 'all') {
            $mappedStatus = match ($status) {
                'new-request' => 'New Request',
                'awaiting-supervisor' => 'Awaiting Supervisor',
                'in-progress' => 'In Progress',
                'escalated' => 'Escalated',
                'pending-closure' => 'Pending Closure',
                'awaiting-requestor' => 'Awaiting Requestor',
                'closed' => 'Closed',
                default => null
            };
            if ($mappedStatus === 'New Request') {
                $query->where('status', 'New Request')->whereNull('assigned_to');
            } elseif ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('status', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        match ($sort) {
            'oldest' => $query->reorder()->orderBy('created_at', 'asc'),
            'priority' => $query->reorder()->orderByRaw("CASE
                WHEN ticket_type = 'High'   THEN 1
                WHEN ticket_type = 'Medium' THEN 2
                WHEN ticket_type = 'Low'    THEN 3
                ELSE 4 END"),
            default => null
        };

        $tickets = $query->paginate(10)->withQueryString();

        // Counts
        $counts = [
            'new_request' => Tickets::where('status', 'New Request')->whereNull('assigned_to')->count(),
            'awaiting_supervisor' => Tickets::where('status', 'Awaiting Supervisor')->count(),
            'in_progress' => Tickets::where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('status', 'Escalated')->count(),
            'pending_closure' => Tickets::where('status', 'Pending Closure')->count(),
            'awaiting_requestor' => Tickets::where('status', 'Awaiting Requestor')->count(),
            'closed' => Tickets::where('status', 'Closed')->count(),
        ];
        $counts['active'] = Tickets::whereIn('status', $activeStatuses)->count();

        // Technicians with active ticket count
        $technicians = User::whereHas('role', fn($q) =>
            $q->where('role_name', 'IT Support Specialist'))
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', ['In Progress', 'Open'])
            ])
            ->get()
            ->map(function ($tech) {
                $tech->availability = match (true) {
                    $tech->active_tickets === 0 => 'free',
                    $tech->active_tickets <= 2 => 'busy',
                    default => 'full'
                };
                return $tech;
            });


        // ── Load SLA categories with their active rules for the ticket modal
        $slaCategories = \App\Models\SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority')
                    ->orderBy('subcategory_name');
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Add this RIGHT AFTER $slaCategories is built, before the return statement
        $slaCategoriesJson = $slaCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'icon' => $c->icon,
            'color' => $c->color,
            'subs' => $c->rules->map(fn($r) => [
                'name' => $r->subcategory_name,
                'priority' => $r->priority,
            ])->values()->toArray(),
        ])->values()->toArray();

        $users = \App\Models\User::select(
            'users.id',
            'users.name',
            'users.position',
            'departments.department_name',
            'companies.company_name',
            'business_units.business_units_name'
        )
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('companies', 'companies.id', '=', 'departments.companies_id')
            ->leftJoin('business_units', 'business_units.id', '=', 'companies.business_units_id')
            ->orderBy('users.name')
            ->get();

        return view('dashboard.helpdesk', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'slaCategories',
            'slaCategoriesJson',
            'users'
        ));
    }

    // Acknowledge ticket (Open → Open with acknowledgment note)
    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->status !== 'New Request') {
            return back()->with('error', 'Only Open tickets can be acknowledged.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Supervisor',
            'date_acknowledged' => now()->toDateString(),   // YYYY-MM-DD
            'time_acknowledged' => now()->toTimeString(),   // HH:MM:SS
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Supervisor',
            'changed_by' => Auth::id(),
            'notes' => 'Ticket acknowledged by Helpdesk — ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        $supervisors = User::withActiveRole('Supervisor - Support Specialist')->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(
                new TicketAssignedMail($ticket, 'A ticket has been acknowledged by Helpdesk and needs classification & assignment.', 'supervisor.support.dashboard')
            );
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged.");
    }
    public function closenotify(Tickets $ticket)
    {
        // Only allow Pending Closure
        if ($ticket->status !== 'Pending Closure') {
            return back()->with('error', 'Only Pending Closure tickets can be closed.');
        }

        $oldStatus = $ticket->status;

        // Update ticket
        $ticket->update([
            'status' => 'Awaiting Requestor',
            'closed_at' => now(), // optional if you have this column
        ]);

        // Log history (FIXED)
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Requestor',
            'changed_by' => Auth::id(),
            'notes' => 'Ticket closed by helpdesk and notification sent by ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        // Send email to requester (employee) — resolved, awaiting their confirmation
        if ($ticket->user && $ticket->user->email) {
            Mail::to($ticket->user->email)->send(new TicketResolvedMail($ticket));
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} closed and user notified.");
    }
    // Assign technician
    // public function assign(Request $request, Tickets $ticket)
    // {
    //     $request->validate([
    //         'technician_id' => 'required|uuid|exists:users,id',
    //         'notes' => 'nullable|string|max:500',
    //     ]);

    //     $oldStatus = $ticket->status;
    //     $tech = User::findOrFail($request->technician_id);

    //     $ticket->update([
    //         'assigned_to' => $request->technician_id,
    //         'status' => 'In Progress',
    //         'started_at' => now(),
    //     ]);

    //     TicketStatusHistories::create([
    //         'ticket_id' => $ticket->id,
    //         'old_status' => $oldStatus,
    //         'new_status' => 'In Progress',
    //         'changed_by' => Auth::id(),
    //         'notes' => "Assigned to {$tech->name} by Helpdesk."
    //             . ($request->notes ? " Note: {$request->notes}" : ''),
    //         'changed_at' => now(),
    //     ]);

    //     return back()->with(
    //         'success',
    //         "Ticket #{$ticket->ticket_number} assigned to {$tech->name}."
    //     );
    // }

    // Reassign technician
    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'New Request';
        $newTech = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => 'In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name} by Helpdesk."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} reassigned to {$newTech->name}."
        );
    }

    // Escalate to IT Admin
    public function escalate(Request $request, Tickets $ticket)
    {
        $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Escalated',
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        // Log escalation record
        DB::table('escalations')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'ticket_id' => $ticket->id,
            'escalation_level' => $ticket->escalation_level,
            'escalated_by' => Auth::id(),
            'previous_tech_id' => $ticket->assigned_to,
            'reassigned_to' => null,
            'reason' => $request->reason,
            'resolution_notes' => $request->notes,
            'escalated_at' => now(),
            'resolved_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Escalated',
            'changed_by' => Auth::id(),
            'notes' => "Escalated to IT Admin. Reason: {$request->reason}."
                . ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to IT Admin."
        );
    }

    // Mark as resolved
    public function resolve(Request $request, Tickets $ticket)
    {
        $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Resolved',
            'changed_by' => Auth::id(),
            'notes' => "Resolved by Helpdesk: {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} marked as resolved."
        );
    }
    // Add this static helper at the bottom of the class
    public static function formatMinsLeft(float $m): string
    {
        if ($m <= 0)
            return 'Overdue';
        if ($m < 60)
            return intval($m) . 'm left';
        $h = floor($m / 60);
        $min = intval($m % 60);
        return $h . 'h' . ($min > 0 ? ' ' . $min . 'm' : '') . ' left';
    }
}