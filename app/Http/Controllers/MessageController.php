<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // Get all messages for a ticket (polling)
    // ── Get all messages for a ticket
    public function index(Tickets $ticket)
    {
        $this->authorizeAccess($ticket);

        // ── Use DB::table() to avoid Eloquent adding updated_at
        \DB::table('ticket_messages')
            ->where('ticket_id', $ticket->id)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $messages = TicketMessage::where('ticket_id', $ticket->id)
            ->with('sender.role')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                $nameParts = explode(' ', $msg->sender->name ?? 'Unknown User');
                $initials = strtoupper(substr($nameParts[0], 0, 1)) .
                    strtoupper(substr(end($nameParts), 0, 1));

                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_id' => $msg->sender_id,
                    'sender' => $msg->sender->name ?? 'Unknown',
                    'role' => $msg->sender->role?->role_name ?? 'User',
                    'initials' => $initials,
                    'is_me' => $msg->sender_id === Auth::id(),
                    'is_read' => $msg->is_read,
                    'time' => \Carbon\Carbon::parse($msg->created_at)->format('M d, g:i A'),
                    'time_ago' => \Carbon\Carbon::parse($msg->created_at)->diffForHumans(),
                    'created_at' => $msg->created_at,
                ];
            });

        return response()->json([
            'messages' => $messages,
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
            ],
        ]);
    }

    // Send a message
    public function store(Request $request, Tickets $ticket)
    {
        $this->authorizeAccess($ticket);

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'is_read' => false,
            'created_at' => now(),
        ]);

        $msg->load('sender.role');

        $nameParts = explode(' ', $msg->sender->name);
        $initials = strtoupper(substr($nameParts[0], 0, 1)) .
            strtoupper(substr(end($nameParts), 0, 1));

        return response()->json([
            'id' => $msg->id,
            'message' => $msg->message,
            'sender_id' => $msg->sender_id,
            'sender' => $msg->sender->name,
            'role' => $msg->sender->role?->role_name,
            'initials' => $initials,
            'is_me' => true,
            'is_read' => false,
            'time' => $msg->created_at->format('M d, g:i A'),
            'time_ago' => $msg->created_at->diffForHumans(),
        ]);
    }

    // Get unread message count for a ticket
    public function unreadCount(Tickets $ticket)
    {
        $count = TicketMessage::where('ticket_id', $ticket->id)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // Get total unread count across all tickets (for nav badge)
    public function totalUnread()
    {
        $user = Auth::user();

        // Get tickets this user is involved in
        $ticketIds = Tickets::where(function ($q) use ($user) {
            $q->where('users_id', $user->id)
                ->orWhere('assigned_to', $user->id);
        })->pluck('id');

        $count = TicketMessage::whereIn('ticket_id', $ticketIds)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // Authorize user can access ticket messages
    private function authorizeAccess(Tickets $ticket): void
    {
        $user = Auth::user();
        $roleName = $user->role?->role_name;

        $canAccess = match ($roleName) {
            'IT Admin' => true, // Admin can see all
            'Employee' => $ticket->users_id === $user->id,
            'Helpdesk' => true, // Helpdesk sees all tickets
            'IT Technician' => $ticket->assigned_to === $user->id,
            'Executive' => true,
            default => false,
        };

        if (!$canAccess) {
            abort(403, 'You do not have access to this ticket.');
        }
    }
}