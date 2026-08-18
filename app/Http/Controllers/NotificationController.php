<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Models\TicketMessage;
use App\Models\ReclassificationRequest;
use App\Support\TicketStatus;
use App\Support\TicketReportProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function poll()
    {
        $user = Auth::user();
        $roleName = $user->role?->role_name;
        $since = request('since', now()->subSeconds(35)->toISOString());
        $sinceTs = \Carbon\Carbon::parse($since);

        $notifications = collect();

        // ── New messages across all accessible tickets
        $ticketIds = $this->getAccessibleTicketIds($user, $roleName);

        $newMessages = TicketMessage::whereIn('ticket_id', $ticketIds)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->where('created_at', '>=', $sinceTs)
            ->with('sender.role', 'ticket')
            ->get();

        foreach ($newMessages as $msg) {
            $notifications->push([
                'type' => 'new_message',
                'title' => '💬 New Message',
                'body' => "{$msg->sender->name}: {$msg->message}",
                'url' => $this->getTicketUrl($roleName, $msg->ticket),
                'tag' => 'msg-' . $msg->id,
                'time' => $msg->created_at,
            ]);
        }

        // ── New tickets (Helpdesk + Admin only)
        // 'Open' was never actually a live status — this filter never matched
        // anything. Fixed to the real "not yet acknowledged" status.
        if (in_array($roleName, ['Helpdesk', 'IT Admin'])) {
            $newTickets = DB::table('tickets')
                ->where('created_at', '>=', $sinceTs)
                ->where('status', TicketStatus::FOR_ACKNOWLEDGMENT)
                ->get();

            foreach ($newTickets as $ticket) {
                $notifications->push([
                    'type' => 'new_ticket',
                    'title' => '🎫 New Ticket Submitted',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => $this->getDashboardUrl($roleName),
                    'tag' => 'ticket-' . $ticket->id,
                    'time' => $ticket->created_at,
                ]);
            }
        }

        // ── Ticket status changes (Employee only)
        if ($roleName === 'Employee') {
            $myTickets = Tickets::where('users_id', $user->id)
                ->where('updated_at', '>=', $sinceTs)
                ->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::ESCALATED])
                ->get();

            foreach ($myTickets as $ticket) {
                $emoji = match ($ticket->status) {
                    TicketStatus::IN_PROGRESS_SERVICE_REQUEST => '⚙️',
                    TicketStatus::ESCALATED => '⚠️',
                    default => '🔔'
                };
                $notifications->push([
                    'type' => 'status_change',
                    'title' => "{$emoji} Ticket {$ticket->status}",
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('employee.tickets.show', $ticket->id),
                    'tag' => 'status-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }
        }

        // ── New assignments (IT Technician only)
        if ($roleName === 'IT Technician') {
            $newAssigned = Tickets::where('assigned_to', $user->id)
                ->where('updated_at', '>=', $sinceTs)
                ->where('status', TicketStatus::ASSIGNED)
                ->get();

            foreach ($newAssigned as $ticket) {
                $notifications->push([
                    'type' => 'new_assignment',
                    'title' => '📋 New Ticket Assigned',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('technician.dashboard'),
                    'tag' => 'assign-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }
        }

        // ── Items needing your action (Support Specialist Supervisor only) —
        // mirrors the four "attention banner" queues on that dashboard:
        // classification, tech acknowledgment, reclassification, report review.
        if ($roleName === 'Supervisor - Support Specialist') {
            $newClassification = Tickets::where('status', TicketStatus::CLASSIFIED)
                ->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR)
                ->whereNull('assigned_to')
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newClassification as $ticket) {
                $notifications->push([
                    'type' => 'awaiting_classification',
                    'title' => '🏷️ Needs Classification',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('supervisor.support.dashboard', ['status' => 'awaiting-classification']),
                    'tag' => 'class-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }

            $newTechAck = TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ASSIGNED, ['IT Support Specialist'])
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newTechAck as $ticket) {
                $notifications->push([
                    'type' => 'awaiting_tech_ack',
                    'title' => '📥 Awaiting Specialist Acknowledgment',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('supervisor.support.dashboard', ['status' => 'awaiting-tech-ack']),
                    'tag' => 'techack-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }

            $newReclassifications = ReclassificationRequest::where('status', 'pending')
                ->where('requested_at', '>=', $sinceTs)
                ->with('ticket')
                ->get();

            foreach ($newReclassifications as $req) {
                if (!$req->ticket) {
                    continue;
                }
                $notifications->push([
                    'type' => 'pending_reclassification',
                    'title' => '🔁 Reclassification Requested',
                    'body' => "#{$req->ticket->ticket_number} — {$req->ticket->subject}",
                    'url' => route('supervisor.support.dashboard', ['status' => 'pending-reclassification']),
                    'tag' => 'reclass-' . $req->id,
                    'time' => $req->requested_at,
                ]);
            }

            $newReportReview = TicketReportProgress::forRoles(Tickets::query(), TicketStatus::REPORT_FOR_REVIEW, ['IT Support Specialist', 'Helpdesk'])
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newReportReview as $ticket) {
                $notifications->push([
                    'type' => 'pending_supervisor_approval',
                    'title' => '📄 Report Ready For Review',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('supervisor.support.dashboard', ['status' => 'pending-supervisor-approval']),
                    'tag' => 'report-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }
        }

        // ── Items needing your action (IT Admin Supervisor only) — mirrors the
        // Support Specialist Supervisor block above, scoped to IT Admin's queue.
        if ($roleName === 'Supervisor - IT Admin') {
            $newAdminClassification = Tickets::whereIn('status', [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::CLASSIFIED])
                ->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR)
                ->whereNull('assigned_to')
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newAdminClassification as $ticket) {
                $notifications->push([
                    'type' => 'awaiting_admin_classification',
                    'title' => '🏷️ Needs Classification',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('supervisor.dashboard', ['status' => 'awaiting-admin-classification']),
                    'tag' => 'admin-class-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }

            $newAdminAck = TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ASSIGNED, ['IT Admin'])
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newAdminAck as $ticket) {
                $notifications->push([
                    'type' => 'awaiting_administrator_ack',
                    'title' => '📥 Awaiting IT Admin Acknowledgment',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('supervisor.dashboard', ['status' => 'awaiting-administrator-ack']),
                    'tag' => 'admin-ack-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }

            $newAdminReclassifications = ReclassificationRequest::where('status', 'pending')
                ->where('requested_at', '>=', $sinceTs)
                ->whereHas('ticket.assignedTo.role', fn ($q) => $q->where('role_name', 'IT Admin'))
                ->with('ticket')
                ->get();

            foreach ($newAdminReclassifications as $req) {
                if (!$req->ticket) {
                    continue;
                }
                $notifications->push([
                    'type' => 'pending_admin_reclassification',
                    'title' => '🔁 Reclassification Requested',
                    'body' => "#{$req->ticket->ticket_number} — {$req->ticket->subject}",
                    'url' => route('supervisor.dashboard', ['status' => 'pending-reclassification']),
                    'tag' => 'admin-reclass-' . $req->id,
                    'time' => $req->requested_at,
                ]);
            }

            $newAdminReportReview = TicketReportProgress::forRoles(Tickets::query(), TicketStatus::REPORT_FOR_REVIEW, ['IT Admin'])
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newAdminReportReview as $ticket) {
                $notifications->push([
                    'type' => 'pending_admin_supervisor_approval',
                    'title' => '📄 Report Ready For Review',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('supervisor.dashboard', ['status' => 'pending-admin-supervisor-approval']),
                    'tag' => 'admin-report-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }
        }

        // ── Escalations (IT Admin only)
        if ($roleName === 'IT Admin') {
            $newEscalations = Tickets::where('status', TicketStatus::ESCALATED)
                ->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR)
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newEscalations as $ticket) {
                $notifications->push([
                    'type' => 'escalation',
                    'title' => '🚨 Ticket Escalated',
                    'body' => "#{$ticket->ticket_number} requires admin attention",
                    'url' => route('admin.dashboard'),
                    'tag' => 'esc-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }
        }

        // ── Escalations to Manager
        if ($roleName === 'Manager') {
            $newManagerEscalations = Tickets::where('status', TicketStatus::ESCALATED)
                ->where('pending_role', TicketStatus::QUEUE_MANAGER)
                ->where('updated_at', '>=', $sinceTs)
                ->get();

            foreach ($newManagerEscalations as $ticket) {
                $notifications->push([
                    'type' => 'escalation',
                    'title' => '🚨 Ticket Escalated to You',
                    'body' => "#{$ticket->ticket_number} — {$ticket->subject}",
                    'url' => route('executive.tickets.index'),
                    'tag' => 'esc-' . $ticket->id,
                    'time' => $ticket->updated_at,
                ]);
            }
        }

        return response()->json([
            'notifications' => $notifications->sortByDesc('time')->values(),
            'server_time' => now()->toISOString(),
        ]);
    }

    private function getAccessibleTicketIds($user, $roleName): \Illuminate\Support\Collection
    {
        return match ($roleName) {
            'IT Admin', 'Helpdesk', 'Manager' => DB::table('tickets')->pluck('id'),
            'IT Technician' => DB::table('tickets')->where('assigned_to', $user->id)->pluck('id'),
            default => DB::table('tickets')->where('users_id', $user->id)->pluck('id'),
        };
    }

    private function getTicketUrl($roleName, $ticket): string
    {
        return match ($roleName) {
            'Employee' => route('employee.tickets.show', $ticket->id),
            'Helpdesk' => route('helpdesk.dashboard'),
            'IT Technician' => route('technician.dashboard'),
            'IT Admin' => route('admin.dashboard'),
            'Manager' => route('executive.tickets.index'),
            default => '/',
        };
    }

    private function getDashboardUrl($roleName): string
    {
        return match ($roleName) {
            'Employee' => route('employee.tickets.index'),
            'Helpdesk' => route('helpdesk.dashboard'),
            'IT Support Specialist' => route('technician.dashboard'),
            'IT Admin' => route('admin.dashboard'),
            'Manager' => route('executive.tickets.index'),
            default => '/',
        };
    }
}