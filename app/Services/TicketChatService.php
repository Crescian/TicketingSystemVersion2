<?php

namespace App\Services;

use App\Jobs\SendTicketChatDigest;
use App\Models\TicketMessage;
use App\Models\Tickets;
use App\Models\User;
use App\Support\RequestorPush;
use App\Support\StaffNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Ticket chat rules shared by the web chat panel (App\Http\Controllers\
// MessageController) and the mobile API (App\Http\Controllers\Api\Mobile\
// MessageController) — who may see a ticket's chat, read receipts, sending
// (text and/or attachments) and the requestor notifications a staff reply
// triggers. Each controller keeps its own response shape.
class TicketChatService
{
    public function __construct(private RequestorTicketService $requestorTickets)
    {
    }

    public function canAccess(Tickets $ticket, User $user): bool
    {
        // The requestor always has access to their own ticket's chat, even when
        // the requestor is ICT staff (e.g. a Technician's self-filed ticket won't
        // be assigned to themselves, so the staff-role checks below wouldn't
        // otherwise cover it).
        return $ticket->users_id === $user->id || match ($user->role?->role_name) {
            'IT Admin' => true, // Admin can see all
            'Helpdesk' => true, // Helpdesk sees all tickets
            'IT Support Specialist' => $ticket->assigned_to === $user->id,
            'Supervisor - Support Specialist' => true,
            'Supervisor - IT Admin' => true,
            'Manager' => true,
            default => false,
        };
    }

    public function authorize(Tickets $ticket, User $user): void
    {
        if (!$this->canAccess($ticket, $user)) {
            abort(403, 'You do not have access to this ticket.');
        }
    }

    // Marks everything the other side sent as read. DB::table() so Eloquent
    // doesn't try to touch an updated_at column ticket_messages doesn't have.
    public function markRead(Tickets $ticket, User $reader): void
    {
        DB::table('ticket_messages')
            ->where('ticket_id', $ticket->id)
            ->where('sender_id', '!=', $reader->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function messages(Tickets $ticket): Collection
    {
        return TicketMessage::where('ticket_id', $ticket->id)
            ->with(['sender.role', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // Text, attachments (image/PDF), or both — callers validate that at least
    // one is present.
    public function send(Tickets $ticket, User $sender, ?string $message, array $files): TicketMessage
    {
        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $sender->id,
            'message' => $message ?? '',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->requestorTickets->storeAttachments($ticket, $files, $sender, $msg->id);

        $preview = $sender->name . ': ' . ($message !== null && $message !== '' ? Str::limit($message, 120) : 'sent an attachment');

        // Only staff replies need to notify the requestor — they already know
        // what they themselves just typed. Email is a debounced digest; the
        // mobile push goes out per message.
        if ($ticket->users_id !== $sender->id) {
            SendTicketChatDigest::scheduleIfNeeded($ticket);

            RequestorPush::send($ticket, 'new_message', "New message on #{$ticket->ticket_number}", $preview);
        } else {
            // The requester wrote: tell whoever is working the ticket on the
            // mobile app, or Helpdesk while nobody is assigned yet.
            $recipients = $ticket->assigned_to && $ticket->assigned_to !== $sender->id
                ? User::whereKey($ticket->assigned_to)->get()
                : ($ticket->assigned_to ? collect() : User::withActiveRole('Helpdesk')->get());

            StaffNotifier::notify($recipients, 'new_message', "New message on #{$ticket->ticket_number}", $preview, $ticket->id);
        }

        return $msg->load(['sender.role', 'attachments']);
    }

    public function unreadCount(Tickets $ticket, User $user): int
    {
        return TicketMessage::where('ticket_id', $ticket->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    // Unread across every ticket the user filed or is assigned to (nav badge).
    public function totalUnread(User $user): int
    {
        $ticketIds = Tickets::where(function ($q) use ($user) {
            $q->where('users_id', $user->id)
                ->orWhere('assigned_to', $user->id);
        })->pluck('id');

        return TicketMessage::whereIn('ticket_id', $ticketIds)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    // Unread count per ticket the user filed or is assigned to, keyed by
    // ticket id (tickets with nothing unread are left out).
    public function unreadByTicket(User $user): Collection
    {
        return TicketMessage::query()
            ->join('tickets', 'tickets.id', '=', 'ticket_messages.ticket_id')
            ->where(fn ($q) => $q->where('tickets.users_id', $user->id)->orWhere('tickets.assigned_to', $user->id))
            ->where('ticket_messages.sender_id', '!=', $user->id)
            ->where('ticket_messages.is_read', false)
            ->groupBy('ticket_messages.ticket_id')
            ->selectRaw('ticket_messages.ticket_id, count(*) as total')
            ->pluck('total', 'ticket_id')
            ->map(fn ($n) => (int) $n);
    }

    // Two-letter avatar initials ("Juan Dela Cruz" -> "JC").
    public static function initials(?string $name): string
    {
        $parts = explode(' ', $name ?: 'Unknown User');

        return strtoupper(substr($parts[0], 0, 1)) . strtoupper(substr(end($parts), 0, 1));
    }
}
