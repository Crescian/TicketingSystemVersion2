<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Models\TicketAttachment;
use App\Services\TicketChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(private TicketChatService $chat)
    {
    }

    // ── Get all messages for a ticket (polling) — also marks the other side's
    //    messages as read.
    public function index(Tickets $ticket)
    {
        $this->chat->authorize($ticket, Auth::user());
        $this->chat->markRead($ticket, Auth::user());

        $messages = $this->chat->messages($ticket)
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_id' => $msg->sender_id,
                    'sender' => $msg->sender->name ?? 'Unknown',
                    'role' => $msg->sender->role?->role_name ?? 'User',
                    'initials' => TicketChatService::initials($msg->sender->name ?? null),
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
        $this->chat->authorize($ticket, Auth::user());

        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf',
        ]);

        if (! $request->filled('message') && ! $request->hasFile('attachments')) {
            return response()->json(['message' => 'Enter a message or attach a file.'], 422);
        }

        $msg = $this->chat->send($ticket, Auth::user(), $request->message, $request->file('attachments', []));

        return response()->json([
            'id' => $msg->id,
            'message' => $msg->message,
            'sender_id' => $msg->sender_id,
            'sender' => $msg->sender->name,
            'role' => $msg->sender->role?->role_name,
            'initials' => TicketChatService::initials($msg->sender->name),
            'is_me' => true,
            'is_read' => false,
            'attachments' => $msg->attachments->map(fn ($a) => $this->formatAttachment($a))->values(),
            'time' => $msg->created_at->format('M d, g:i A'),
            'time_ago' => $msg->created_at->diffForHumans(),
        ]);
    }

    // Get unread message count for a ticket
    public function unreadCount(Tickets $ticket)
    {
        return response()->json(['count' => $this->chat->unreadCount($ticket, Auth::user())]);
    }

    // Get total unread count across all tickets (for nav badge)
    public function totalUnread()
    {
        return response()->json(['count' => $this->chat->totalUnread(Auth::user())]);
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
}
