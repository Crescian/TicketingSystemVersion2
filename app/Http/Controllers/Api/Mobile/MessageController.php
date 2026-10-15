<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MessageResource;
use App\Models\Tickets;
use App\Services\TicketChatService;
use Illuminate\Http\Request;

// Ticket chat for the mobile app — same rules as the web chat panel, via
// TicketChatService.
class MessageController extends Controller
{
    public function __construct(private TicketChatService $chat)
    {
    }

    // All messages, oldest first. Marks the IT side's messages as read.
    public function index(Request $request, Tickets $ticket)
    {
        $this->chat->authorize($ticket, $request->user());
        $this->chat->markRead($ticket, $request->user());

        return MessageResource::collection($this->chat->messages($ticket));
    }

    public function store(Request $request, Tickets $ticket)
    {
        $this->chat->authorize($ticket, $request->user());

        $request->validate([
            'message' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf',
        ]);

        if (!$request->filled('message') && !$request->hasFile('attachments')) {
            return response()->json(['message' => 'Enter a message or attach a file.'], 422);
        }

        $message = $this->chat->send($ticket, $request->user(), $request->message, $request->file('attachments', []));

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    // Total plus per-ticket unread counts for the employee's own tickets.
    public function unread(Request $request)
    {
        $byTicket = $this->chat->unreadByTicket($request->user());

        return response()->json([
            'total' => $byTicket->sum(),
            'by_ticket' => (object) $byTicket->all(),
        ]);
    }
}
