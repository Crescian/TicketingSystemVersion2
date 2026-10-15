<?php

namespace App\Support;

use App\Jobs\SendExpoPush;
use App\Models\Tickets;
use App\Models\UserDevice;

// Push notification to a ticket's requestor on the employee mobile app. Called
// next to the existing requestor-facing emails (tracker step changes in
// TicketObserver, on hold in TicketHold, staff chat replies, auto-close). Only
// queues a job when the requestor actually has a registered device — most
// users don't, so most calls cost one indexed lookup. Wrapped in rescue() so a
// push problem can never break the web action that triggered it.
class RequestorPush
{
    public static function send(Tickets $ticket, string $type, string $title, string $body): void
    {
        rescue(function () use ($ticket, $type, $title, $body) {
            if (!$ticket->users_id || !UserDevice::where('user_id', $ticket->users_id)->exists()) {
                return;
            }

            SendExpoPush::dispatch($ticket->users_id, $title, $body, [
                'type' => $type,
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);
        }, report: false);
    }
}
