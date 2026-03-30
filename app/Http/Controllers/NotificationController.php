<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Models\TicketMessage;
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
        if (in_array($roleName, ['Helpdesk', 'IT Admin'])) {
            $newTickets = DB::table('tickets')
                ->where('created_at', '>=', $sinceTs)
                ->where('status', 'Open')
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
                ->whereIn('status', ['In Progress', 'Resolved', 'Escalated'])
                ->get();

            foreach ($myTickets as $ticket) {
                $emoji = match ($ticket->status) {
                    'In Progress' => '⚙️',
                    'Resolved' => '✅',
                    'Escalated' => '⚠️',
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
                ->where('status', 'Open')
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

        // ── Escalations (IT Admin only)
        if ($roleName === 'IT Admin') {
            $newEscalations = Tickets::where('status', 'Escalated')
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
            'Manager' => route('executive.dashboard'),
            default => '/',
        };
    }
}