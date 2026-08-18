<?php

namespace App\Support;

use App\Models\Tickets;

// Every notification tied to a ticket anchors to this same Message-ID as its
// thread root, so mail clients (Gmail/Outlook) collapse the whole lifecycle —
// submitted, assigned, resolved, awaiting confirmation — into one thread
// instead of a separate email per stage.
class TicketMailThread
{
    public static function rootMessageId(Tickets $ticket): string
    {
        $slug = preg_replace('/[^A-Za-z0-9.-]/', '-', (string) $ticket->ticket_number);

        return "ticket-{$slug}@leoniogroup.com";
    }
}
