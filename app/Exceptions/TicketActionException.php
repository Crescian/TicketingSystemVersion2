<?php

namespace App\Exceptions;

use RuntimeException;

// A requestor action that isn't allowed in the ticket's current state (e.g.
// cancelling after Helpdesk acknowledged it). The message is user-facing — the
// web controllers flash it with back()->with('error'), the mobile API returns
// it as a 422 JSON { message }.
class TicketActionException extends RuntimeException
{
}
