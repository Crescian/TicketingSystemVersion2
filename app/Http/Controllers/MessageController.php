<?php

namespace App\Http\Controllers;

use App\Jobs\SendTicketChatDigest;
use App\Models\Tickets;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            ->with(['sender.role', 'attachments'])
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
                    'time' => \Carbon\Carbon::parse($msg->created_at)->timezone('Asia/Manila')->format('M d, g:i A'),
                    'time_ago' => \Carbon\Carbon::parse($msg->created_at)->diffForHumans(),
                    'created_at' => $msg->created_at,
                    'attachments' => $msg->attachments->map(fn ($a) => $this->formatAttachment($a))->values(),
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

    // Send a message — text, attachments (image/PDF), or both. At least one
    // of the two is required.
    public function store(Request $request, Tickets $ticket)
    {
        $this->authorizeAccess($ticket);

        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf',
        ]);

        if (! $request->filled('message') && ! $request->hasFile('attachments')) {
            return response()->json(['message' => 'Enter a message or attach a file.'], 422);
        }

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'message' => $request->message ?? '',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $attachments = collect($request->file('attachments', []))->map(function ($file) use ($ticket, $msg) {
            $storedPath = $file->store('ticket-attachments/' . $ticket->id, 'local');

            return TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'ticket_message_id' => $msg->id,
                'uploaded_by' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        });

        // Only staff replies need to notify the employee by email — the
        // employee already knows what they themselves just typed.
        if ($ticket->users_id !== $msg->sender_id) {
            SendTicketChatDigest::scheduleIfNeeded($ticket);
        }

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
            'attachments' => $attachments->map(fn ($a) => $this->formatAttachment($a))->values(),
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

    // Shared shape for a chat attachment — reuses the same view/download routes
    // (and authorization) as ticket-level attachments, since a chat attachment
    // is just a TicketAttachment row that also happens to be tied to a message.
    private function formatAttachment(TicketAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->humanSize(),
            'view_url' => route('attachments.view', $attachment),
            'download_url' => route('attachments.download', $attachment),
        ];
    }

    // Authorize user can access ticket messages
    private function authorizeAccess(Tickets $ticket): void
    {
        $user = Auth::user();
        $roleName = $user->role?->role_name;

        // ── The requestor always has access to their own ticket's chat, even
        //    when the requestor is ICT staff (e.g. a Technician's self-filed
        //    ticket won't be assigned to themselves, so the staff-role checks
        //    below wouldn't otherwise cover it).
        $canAccess = $ticket->users_id === $user->id || match ($roleName) {
            'IT Admin' => true, // Admin can see all
            'Helpdesk' => true, // Helpdesk sees all tickets
            'IT Support Specialist' => $ticket->assigned_to === $user->id,
            'Supervisor - Support Specialist' => true,
            'Supervisor - IT Admin' => true,
            'Manager' => true,
            default => false,
        };

        if (!$canAccess) {
            abort(403, 'You do not have access to this ticket.');
        }
    }
}