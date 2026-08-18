<?php

namespace App\Support;

// Shared validation for the "resolve this ticket" action across Technician, Admin,
// and Manager tracks — keeps the required fields consistent across tracks. Each
// controller still merges its own track-specific fields on top (Technician:
// findings/recommendation/attachments, Admin: root_cause) and still decides its own
// next status/approval routing, which intentionally differs per track.
class TicketResolutionRules
{
    public const BASE = [
        'resolution_notes' => 'required|string',
        'service_type' => 'required|in:Onsite,Remote,Preventive',
    ];
}
